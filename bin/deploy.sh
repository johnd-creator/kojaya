#!/usr/bin/env bash
set -euo pipefail

deploy_ref=''

while (($# > 0)); do
    case "$1" in
        --ref)
            if (($# < 2)) || [[ -n "$deploy_ref" ]]; then
                printf 'Missing value for --ref.\n' >&2
                exit 2
            fi

            deploy_ref="$2"
            shift 2
            ;;
        *)
            printf 'Unknown deployment argument.\n' >&2
            exit 2
            ;;
    esac
done

if [[ ! "$deploy_ref" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Deployment requires an exact 40-character commit SHA.\n' >&2
    exit 2
fi

# Never carry local edits or untracked application files into an approved release.
# Ignored runtime files (.env, private storage, vendor) remain operator-managed.
if ! worktree_status="$(git status --porcelain=v1 --untracked-files=all)"; then
    printf 'Cannot inspect deployment worktree.\n' >&2
    exit 1
fi
if [[ -n "$worktree_status" ]]; then
    printf 'Deployment requires a clean worktree, including untracked files.\n' >&2
    exit 1
fi

git fetch --prune origin \
    '+refs/heads/*:refs/remotes/origin/*' \
    '+refs/tags/*:refs/tags/*'

if ! target_commit="$(git rev-parse --verify "$deploy_ref^{commit}" 2>/dev/null)"; then
    printf 'Deployment SHA could not be resolved to a commit.\n' >&2
    exit 1
fi

if [[ ! "$target_commit" =~ ^[0-9a-fA-F]{40}$ ]] || [[ "$target_commit" != "${deploy_ref,,}" ]]; then
    printf 'Resolved commit does not match the approved exact SHA.\n' >&2
    exit 1
fi

if ! previous_commit="$(git rev-parse --verify HEAD^{commit} 2>/dev/null)"; then
    printf 'Current application revision could not be resolved to a commit.\n' >&2
    exit 1
fi

if [[ ! "$previous_commit" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Current application revision could not be resolved to a commit.\n' >&2
    exit 1
fi

printf 'Deployment requested %s; resolved %s; previous %s.\n' "$deploy_ref" "$target_commit" "$previous_commit"

maintenance_active=false
deployment_succeeded=false
deployment_stage=backup
migration_state=not-started

cleanup() {
    local status="$?"

    if [[ "$maintenance_active" == true && "$deployment_succeeded" != true ]]; then
        printf 'Deployment failed: stage=%s migration=%s target=%s previous=%s. Maintenance may be active; retain the external traffic/worker hold and inspect before recovery.\n' \
            "$deployment_stage" "$migration_state" "$target_commit" "$previous_commit" >&2
    fi

    exit "$status"
}

trap cleanup EXIT

printf 'Executing pre-deployment database backup and verification...\n'
if ! php artisan backup:database --purpose=pre-deploy; then
    printf 'Pre-deployment backup failed! Aborting deployment before entering maintenance mode or modifying code/database.\n' >&2
    exit 1
fi

deployment_stage=maintenance
# Mark before the command: failure can leave maintenance partially established.
maintenance_active=true
php artisan down --retry=60

deployment_stage=checkout
git checkout --detach "$target_commit"

deployment_stage=composer
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
deployment_stage=cache-clear
php artisan optimize:clear
deployment_stage=preflight
php artisan app:release-preflight --strict-production --require-android-push

deployment_stage=npm-install
npm ci --prefer-offline --no-audit
deployment_stage=build
npm run build

deployment_stage=migration
migration_state=started-inspect-ledger
php artisan migrate --force
migration_state=completed
deployment_stage=optimize
php artisan optimize
deployment_stage=queue-restart
php artisan queue:restart

deployment_stage=application-up
php artisan up
maintenance_active=false
deployment_succeeded=true
printf 'Deployment commands completed for %s. Operator smoke acceptance is still required; retain the external traffic/worker hold.\n' "$target_commit"
