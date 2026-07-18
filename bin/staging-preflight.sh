#!/usr/bin/env bash
set -euo pipefail

expected_sha=''
health_url=''
strict_production=false

usage() {
    cat <<'EOF'
Usage: bin/staging-preflight.sh --sha <40-character-commit-sha> --health-url <url> [--strict-production]

The command is read-only against the application. It verifies the checked-out
revision, release preflight, database migration visibility, queue/scheduler
command availability, storage access, and a health endpoint. It never prints
credential or response values.
EOF
}

while (($# > 0)); do
    case "$1" in
        --sha)
            if (($# < 2)) || [[ -n "$expected_sha" ]]; then
                printf '%s\n' 'Missing or duplicate value for --sha.' >&2
                exit 2
            fi

            expected_sha="$2"
            shift 2
            ;;
        --health-url)
            if (($# < 2)) || [[ -n "$health_url" ]]; then
                printf '%s\n' 'Missing or duplicate value for --health-url.' >&2
                exit 2
            fi

            health_url="$2"
            shift 2
            ;;
        --strict-production)
            strict_production=true
            shift
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            printf 'Unknown argument: %s\n' "$1" >&2
            exit 2
            ;;
    esac
done

if [[ ! "$expected_sha" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf '%s\n' 'An exact 40-character commit SHA is required.' >&2
    exit 2
fi

if [[ -z "$health_url" ]]; then
    printf '%s\n' '--health-url is required.' >&2
    exit 2
fi

if actual_sha="$(git rev-parse HEAD 2>/dev/null)"; then
    :
else
    actual_sha=''
fi
if [[ ! "$actual_sha" =~ ^[0-9a-fA-F]{40}$ ]] || [[ "$actual_sha" != "$expected_sha" ]]; then
    printf 'deployment_sha: FAIL\n' >&2
    exit 1
fi

printf 'deployment_sha: PASS (%s)\n' "$actual_sha"

failures=0

run_check() {
    local name="$1"
    shift

    if "$@" >/dev/null 2>&1; then
        printf '%s: PASS\n' "$name"
    else
        printf '%s: FAIL\n' "$name"
        failures=$((failures + 1))
    fi
}

run_check 'working_tree' git diff --quiet
run_check 'index_clean' git diff --cached --quiet

preflight_command=(php artisan app:release-preflight)
if [[ "$strict_production" == true ]]; then
    preflight_command+=(--strict-production)
fi
run_check 'application_preflight' "${preflight_command[@]}"
run_check 'database_migration_status' php artisan migrate:status --no-interaction
run_check 'queue_backend' php artisan queue:failed
run_check 'scheduler_registration' php artisan schedule:list
run_check 'storage_directory' test -d storage
run_check 'storage_writable' test -w storage

response_file="$(mktemp)"
cleanup() {
    rm -f "$response_file"
}
trap cleanup EXIT

if curl --fail --silent --show-error --max-time 15 --output "$response_file" "$health_url" \
    && php -r '
        $payload = json_decode(file_get_contents($argv[1]), true);
        exit(($payload["status"] ?? null) === "ok" ? 0 : 1);
    ' "$response_file" >/dev/null 2>&1; then
    printf '%s\n' 'health_endpoint: PASS'
else
    printf '%s\n' 'health_endpoint: FAIL'
    failures=$((failures + 1))
fi

if ((failures > 0)); then
    printf 'staging_preflight: FAIL (%d check(s))\n' "$failures" >&2
    exit 1
fi

printf '%s\n' 'staging_preflight: PASS'
