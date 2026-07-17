#!/usr/bin/env bash
set -euo pipefail

deploy_ref="main"

while (($# > 0)); do
    case "$1" in
        --ref)
            if (($# < 2)); then
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

if [[ ! "$deploy_ref" =~ ^[A-Za-z0-9][A-Za-z0-9._/-]*$ ]] && [[ ! "$deploy_ref" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Invalid deployment ref.\n' >&2
    exit 2
fi

git fetch --prune origin \
    '+refs/heads/*:refs/remotes/origin/*' \
    '+refs/tags/*:refs/tags/*'

resolve_commit() {
    local candidate
    local commit

    if [[ "$deploy_ref" =~ ^[0-9a-fA-F]{40}$ ]]; then
        git rev-parse --verify "$deploy_ref^{commit}"

        return 0
    fi

    for candidate in "refs/tags/$deploy_ref" "refs/remotes/origin/$deploy_ref" "$deploy_ref"; do
        if commit=$(git rev-parse --verify "$candidate^{commit}" 2>/dev/null); then
            printf '%s\n' "$commit"

            return 0
        fi
    done

    printf 'Deployment ref could not be resolved.\n' >&2

    return 1
}

commit="$(resolve_commit)"
short_commit="${commit:0:12}"
printf 'Deploying ref %s at %s.\n' "$deploy_ref" "$short_commit"

git checkout --detach "$commit"

maintenance_active=false

cleanup() {
    local status="$?"

    if [[ "$maintenance_active" == true ]]; then
        if ! php artisan up; then
            printf 'Deployment failed and application could not be brought up automatically.\n' >&2
            status=1
        else
            printf 'Application brought up during deployment cleanup.\n' >&2
        fi
    fi

    exit "$status"
}

trap cleanup EXIT

php artisan app:release-preflight --strict-production

maintenance_active=true
php artisan down --retry=60

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --prefer-offline --no-audit
npm run build

php artisan migrate --force
php artisan optimize
php artisan queue:restart

php artisan up
maintenance_active=false
