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
            printf 'Unknown argument: %s\n' "$1" >&2
            exit 2
            ;;
    esac
done

if [[ ! "$deploy_ref" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Deployment requires an exact 40-character commit SHA.\n' >&2
    exit 2
fi

git fetch --prune origin \
    '+refs/heads/*:refs/remotes/origin/*' \
    '+refs/tags/*:refs/tags/*'

if ! target_commit="$(git rev-parse --verify "$deploy_ref^{commit}" 2>/dev/null)"; then
    printf 'Deployment SHA could not be resolved to a commit.\n' >&2
    exit 1
fi

if [[ ! "$target_commit" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Deployment SHA could not be resolved to a commit.\n' >&2
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

target_short_commit="${target_commit:0:12}"
previous_short_commit="${previous_commit:0:12}"
printf 'Deployment target %s (previous %s).\n' "$target_short_commit" "$previous_short_commit"

maintenance_active=false
deployment_succeeded=false

cleanup() {
    local status="$?"

    if [[ "$maintenance_active" == true && "$deployment_succeeded" != true ]]; then
        printf 'Deployment failed for target SHA %s (previous %s); application remains in maintenance mode. Manual inspection or rollback is required.\n' \
            "$target_commit" "$previous_short_commit" >&2
    fi

    exit "$status"
}

trap cleanup EXIT

php artisan down --retry=60
maintenance_active=true

git checkout --detach "$target_commit"

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan optimize:clear
php artisan app:release-preflight --strict-production

npm ci --prefer-offline --no-audit
npm run build

php artisan migrate --force
php artisan optimize
php artisan queue:restart

php artisan up
maintenance_active=false
deployment_succeeded=true
