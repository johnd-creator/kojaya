#!/usr/bin/env bash
set -euo pipefail
umask 077

ref=''
approved_sha=''
candidate_dir=''
serving_dir=''
runtime_env=''
traffic_attestation=''

while (($# > 0)); do
    case "$1" in
        --ref|--approved-sha|--candidate-dir|--serving-dir|--runtime-env|--traffic-hold-attestation)
            if (($# < 2)); then
                printf 'Missing value for %s.\n' "$1" >&2
                exit 2
            fi
            case "$1" in
                --ref) ref="$2" ;;
                --approved-sha) approved_sha="$2" ;;
                --candidate-dir) candidate_dir="$2" ;;
                --serving-dir) serving_dir="$2" ;;
                --runtime-env) runtime_env="$2" ;;
                --traffic-hold-attestation) traffic_attestation="$2" ;;
            esac
            shift 2
            ;;
        *)
            printf 'Unknown QA deployment argument.\n' >&2
            exit 2
            ;;
    esac
done

if [[ ! "$ref" =~ ^[0-9a-fA-F]{40}$ || ! "$approved_sha" =~ ^[0-9a-fA-F]{40}$ || "${ref,,}" != "${approved_sha,,}" ]]; then
    printf 'QA deployment requires an exact SHA matching the approved SHA.\n' >&2
    exit 2
fi
if [[ -z "$candidate_dir" || -z "$serving_dir" || -z "$runtime_env" || -z "$traffic_attestation" ]]; then
    printf 'Candidate, serving, runtime configuration, and traffic-hold attestation are required.\n' >&2
    exit 2
fi

candidate_dir="$(cd "$candidate_dir" && pwd -P)"
serving_dir="$(cd "$serving_dir" && pwd -P)"
runtime_env="$(realpath -e "$runtime_env")"
traffic_attestation="$(realpath -e "$traffic_attestation")"
if [[ "$candidate_dir" == "$serving_dir" || "$runtime_env" == "$candidate_dir"/* || "$runtime_env" == "$serving_dir"/* || "$runtime_env" == */public/* || "$traffic_attestation" == "$candidate_dir"/* || "$traffic_attestation" == "$serving_dir"/* ]]; then
    printf 'QA runtime and hold evidence must be external to both worktrees.\n' >&2
    exit 1
fi
if [[ ! -f "$runtime_env" || -L "$runtime_env" || ! -f "$traffic_attestation" || -L "$traffic_attestation" ]]; then
    printf 'QA runtime configuration and hold evidence must be regular protected files.\n' >&2
    exit 1
fi
for protected_file in "$runtime_env" "$traffic_attestation"; do
    permissions="$(stat -c '%a:%u' "$protected_file")"
    if [[ "${permissions%%:*}" != 600 || "${permissions##*:}" != "$(id -u)" ]]; then
        printf 'Protected QA inputs must be owned by the deployment user with mode 0600.\n' >&2
        exit 1
    fi
done

candidate_sha="$(git -C "$candidate_dir" rev-parse --verify HEAD^{commit})"
if [[ "$candidate_sha" != "${ref,,}" ]]; then
    printf 'Candidate worktree SHA does not match the approved exact SHA.\n' >&2
    exit 1
fi
if [[ -n "$(git -C "$candidate_dir" status --porcelain=v1 --untracked-files=all)" ]]; then
    printf 'Candidate worktree must be clean before dependency preparation.\n' >&2
    exit 1
fi
if [[ -n "$(git -C "$serving_dir" status --porcelain=v1 --untracked-files=all)" ]]; then
    printf 'Serving worktree must be clean before QA cutover.\n' >&2
    exit 1
fi
previous_sha="$(git -C "$serving_dir" rev-parse --verify HEAD^{commit})"
if [[ ! "$previous_sha" =~ ^[0-9a-fA-F]{40}$ || "$previous_sha" == "${ref,,}" ]]; then
    printf 'Serving checkout must be a distinct valid previous commit.\n' >&2
    exit 1
fi

serving_env="$serving_dir/.env"
if [[ ! -f "$serving_env" || -L "$serving_env" ]]; then
    printf 'Serving runtime configuration must be a regular non-symlink file.\n' >&2
    exit 1
fi
serving_env_metadata="$(stat -c '%u:%g:%a' -- "$serving_env" 2>/dev/null)" || {
    printf 'Cannot determine serving runtime configuration security metadata.\n' >&2
    exit 1
}
IFS=: read -r serving_env_uid serving_env_gid serving_env_mode <<< "$serving_env_metadata"
if ! [[ "$serving_env_uid" =~ ^[0-9]+$ && "$serving_env_gid" =~ ^[0-9]+$ && "$serving_env_mode" =~ ^[0-7]{3,4}$ ]] \
    || (( (8#$serving_env_mode & 0440) != 0440 )) \
    || (( (8#$serving_env_mode & 07137) != 0 )); then
    printf 'Serving runtime configuration permissions do not match the private PHP-FPM-readable contract.\n' >&2
    exit 1
fi

attestation_epoch="$(sed -n 's/^created_at_epoch=//p' "$traffic_attestation")"
now_epoch="$(date +%s)"
if ! [[ "$attestation_epoch" =~ ^[0-9]{10}$ ]] || (( attestation_epoch > now_epoch || now_epoch - attestation_epoch > 900 )) \
    || [[ "$(grep -c '^environment=qa$' "$traffic_attestation" || true)" != 1 ]] \
    || [[ "$(grep -c '^status=held$' "$traffic_attestation" || true)" != 1 ]] \
    || [[ "$(grep -c "^approved_sha=${ref,,}$" "$traffic_attestation" || true)" != 1 ]] \
    || [[ "$(grep -c '^created_at_epoch=[0-9][0-9]*$' "$traffic_attestation" || true)" != 1 ]]; then
    printf 'Fresh QA traffic-hold attestation is missing or invalid.\n' >&2
    exit 1
fi

systemctl_bin="$(command -v systemctl || true)"
if [[ -z "$systemctl_bin" ]]; then
    printf 'Cannot verify Kojaya QA queue and scheduler service state.\n' >&2
    exit 1
fi
queue_unit='kojaya-qa-queue.service'
scheduler_timer='kojaya-qa-schedule.timer'
if ! queue_load_state="$(systemctl show --property=LoadState --value "$queue_unit" 2>/dev/null)" \
    || ! scheduler_load_state="$(systemctl show --property=LoadState --value "$scheduler_timer" 2>/dev/null)"; then
    printf 'Cannot verify that the required Kojaya QA queue and scheduler units are installed.\n' >&2
    exit 1
fi
if [[ "$queue_load_state" != loaded || "$scheduler_load_state" != loaded ]]; then
    printf 'Required Kojaya QA queue and scheduler units must both be installed.\n' >&2
    exit 1
fi
if ! queue_state="$(systemctl show --property=ActiveState --value "$queue_unit" 2>/dev/null)" \
    || ! scheduler_state="$(systemctl show --property=ActiveState --value "$scheduler_timer" 2>/dev/null)"; then
    printf 'Cannot verify the installed Kojaya QA queue and scheduler state.\n' >&2
    exit 1
fi
scheduler_enabled="$(systemctl is-enabled "$scheduler_timer" 2>/dev/null || true)"
if [[ "$queue_state" != inactive || "$scheduler_state" != inactive || "$scheduler_enabled" != disabled ]]; then
    printf 'Kojaya QA queue must be stopped and scheduler must be stopped and disabled.\n' >&2
    exit 1
fi

candidate_env="$candidate_dir/.env"
if [[ -e "$candidate_env" && ( ! -f "$candidate_env" || -L "$candidate_env" ) ]]; then
    printf 'Candidate runtime configuration path is not a regular file.\n' >&2
    exit 1
fi
install -m 0600 "$runtime_env" "$candidate_env"

run_candidate() {
    (cd "$candidate_dir" && "$@")
}

clear_local_caches() {
    local worktree="$1"
    (cd "$worktree" && php artisan clear-compiled --no-interaction)
    (cd "$worktree" && php artisan config:clear --no-interaction)
    (cd "$worktree" && php artisan route:clear --no-interaction)
    (cd "$worktree" && php artisan view:clear --no-interaction)
    (cd "$worktree" && php artisan event:clear --no-interaction)
}

establish_runtime_permissions() {
    php "$candidate_dir/bin/qa-runtime-permissions.php" "$serving_dir" "$serving_env_gid"
}

printf 'Preparing exact-SHA candidate dependencies.\n'
run_candidate composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
run_candidate npm ci --prefer-offline --no-audit
run_candidate npm run build
if [[ "$(git -C "$candidate_dir" rev-parse --verify HEAD^{commit})" != "${ref,,}" ]] \
    || [[ -n "$(git -C "$candidate_dir" status --porcelain=v1 --untracked-files=all)" ]]; then
    printf 'Candidate source changed during preparation.\n' >&2
    exit 1
fi

clear_local_caches "$candidate_dir"
printf 'Verifying candidate QA database identity.\n'
run_candidate php artisan qa:deployment-identity --expect=kojaya_qa --no-interaction
printf 'Running QA release-candidate preflight.\n'
run_candidate php artisan app:release-preflight --strict-release-candidate --require-android-push --no-interaction

backup_output="$(run_candidate php artisan backup:database --purpose=pre-deploy --disk=local --directory=backups/database --no-interaction)"
backup_id="$(printf '%s\n' "$backup_output" | sed -n 's/^Backup ID:[[:space:]]*//p' | tail -n 1)"
if [[ ! "$backup_id" =~ ^kojaya-qa-kojaya_qa-[0-9]{8}T[0-9]{6}Z-[0-9a-f]{7}$ ]]; then
    printf 'Managed pre-deploy backup did not return the expected QA artifact identity.\n' >&2
    exit 1
fi
printf 'Independently verifying managed QA backup.\n'
run_candidate php artisan backup:verify "backups/database/${backup_id}.dump" --disk=local --directory=backups/database --expected-database=kojaya_qa --require-private-permissions --no-interaction

recovery_root="$(dirname "$runtime_env")/rc11-qa-deploy-recovery"
mkdir -p "$recovery_root"
chmod 0700 "$recovery_root"
recovery_dir="$(mktemp -d "$recovery_root/run.XXXXXXXX")"
chmod 0700 "$recovery_dir"
# Preserve the currently serving runtime contents in the private recovery directory.
install -m 0600 "$serving_env" "$recovery_dir/.env.previous"
printf '%s:%s:%s\n' "$serving_env_uid" "$serving_env_gid" "$serving_env_mode" > "$recovery_dir/.env.previous-metadata"
chmod 0600 "$recovery_dir/.env.previous-metadata"
printf '%s\n' "$previous_sha" > "$recovery_dir/previous-sha"
printf '%s\n' "${ref,,}" > "$recovery_dir/target-sha"
printf '%s\n' 'not-started' > "$recovery_dir/migration-state"

migration_state=not-started
serving_mutated=false
maintenance_may_be_active=false
migration_started=false
failure_stage=traffic-hold

restore_before_migration() {
    local restore_status=0
    if [[ "$serving_mutated" == true && "$migration_started" != true ]]; then
        printf 'Recovering pre-migration QA checkout and runtime configuration.\n' >&2
        git -C "$serving_dir" checkout --detach "$previous_sha" || restore_status=1
        local restore_env_temp
        if restore_env_temp="$(mktemp "$serving_dir/.env.qa-restore.XXXXXXXX")"; then
            if ! install -o "$serving_env_uid" -g "$serving_env_gid" -m "$serving_env_mode" \
                "$recovery_dir/.env.previous" "$restore_env_temp" \
                || ! mv -f -- "$restore_env_temp" "$serving_env"; then
                rm -f -- "$restore_env_temp"
                restore_status=1
            fi
        else
            restore_status=1
        fi
        (cd "$serving_dir" && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader) || restore_status=1
        (cd "$serving_dir" && npm ci --prefer-offline --no-audit && npm run build) || restore_status=1
        clear_local_caches "$serving_dir" || restore_status=1
        establish_runtime_permissions || restore_status=1
        if [[ "$restore_status" -eq 0 ]]; then
            printf 'Pre-migration recovery restored the previous code and runtime configuration; QA remains held.\n' >&2
        else
            printf 'Pre-migration recovery was incomplete; keep QA held and inspect manually.\n' >&2
        fi
    fi
    if [[ "$maintenance_may_be_active" == true ]]; then
        printf 'QA maintenance/traffic hold remains active for operator inspection.\n' >&2
    fi
    return "$restore_status"
}

on_exit() {
    local status="$?"
    trap - EXIT
    if [[ "$status" -ne 0 ]]; then
        printf '%s\n' "$migration_state" > "$recovery_dir/migration-state" 2>/dev/null || true
        restore_before_migration || true
        printf 'QA deployment failed: stage=%s migration=%s previous=%s target=%s. No automatic database recovery was attempted.\n' \
            "$failure_stage" "$migration_state" "$previous_sha" "${ref,,}" >&2
        printf 'Private recovery evidence retained locally at %s.\n' "$recovery_dir" >&2
    fi
    exit "$status"
}
trap on_exit EXIT

failure_stage=runtime-config
serving_mutated=true
serving_env_temp="$(mktemp "$serving_dir/.env.qa-new.XXXXXXXX")"
if ! install -o "$serving_env_uid" -g "$serving_env_gid" -m "$serving_env_mode" "$runtime_env" "$serving_env_temp" \
    || ! mv -f -- "$serving_env_temp" "$serving_env"; then
    rm -f -- "$serving_env_temp"
    printf 'Cannot atomically install QA runtime configuration with approved serving metadata.\n' >&2
    exit 1
fi
clear_local_caches "$serving_dir"

failure_stage=maintenance
maintenance_may_be_active=true
(cd "$serving_dir" && php artisan down --retry=60)

failure_stage=checkout
git -C "$serving_dir" fetch --no-tags "$candidate_dir" "${ref,,}"
git -C "$serving_dir" cat-file -e "${ref,,}^{commit}"
git -C "$serving_dir" checkout --detach "${ref,,}"
if [[ "$(git -C "$serving_dir" rev-parse --verify HEAD^{commit})" != "${ref,,}" ]]; then
    printf 'Active QA checkout did not resolve to the exact requested SHA.\n' >&2
    exit 1
fi

failure_stage=serving-dependencies
(cd "$serving_dir" && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader)
(cd "$serving_dir" && npm ci --prefer-offline --no-audit && npm run build)
clear_local_caches "$serving_dir"
failure_stage=runtime-permissions
establish_runtime_permissions
if [[ -n "$(git -C "$serving_dir" status --porcelain=v1 --untracked-files=all)" ]]; then
    printf 'Serving candidate contains source changes after preparation.\n' >&2
    exit 1
fi

failure_stage=post-cutover-identity
(cd "$serving_dir" && php artisan qa:deployment-identity --expect=kojaya_qa --no-interaction)
(cd "$serving_dir" && php artisan app:release-preflight --strict-release-candidate --require-android-push --no-interaction)

failure_stage=migration
migration_started=true
migration_state=started
printf '%s\n' "$migration_state" > "$recovery_dir/migration-state"
(cd "$serving_dir" && php artisan migrate --force --no-interaction)
migration_state=completed
printf '%s\n' "$migration_state" > "$recovery_dir/migration-state"

failure_stage=post-migration-optimize
(cd "$serving_dir" && php artisan optimize)

failure_stage=post-optimize-permissions
establish_runtime_permissions

failure_stage=controlled-smoke-ready
(cd "$serving_dir" && php artisan up)
maintenance_may_be_active=false
failure_stage=complete
printf 'QA deployment complete: target=%s previous=%s migration=completed; external traffic remains held; queue and scheduler remain stopped.\n' \
    "${ref,,}" "$previous_sha"
printf 'Private previous-runtime and migration-state evidence retained at %s.\n' "$recovery_dir"
