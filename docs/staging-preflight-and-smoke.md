# Staging Preflight and Smoke Runbook

## Status

This runbook is an operational prerequisite for Document 08 Workstream B. It
does not prove that staging exists or that a deployment has succeeded. No
staging host, credential, synthetic account, or provider call was used while
creating this document.

Evidence provenance must identify the staging operator, environment, exact
application SHA, start and finish timestamps, command, result, and a redacted
artifact reference. Secret values, access tokens, member identifiers, and raw
webhook payloads must not be copied into the evidence record.

## Required preconditions

The release owner must confirm all of the following before execution:

- an isolated staging environment and restricted database are available;
- `APP_ENV`, `APP_DEBUG`, `APP_VERSION`, and `API_CONTRACT_VERSION` are owned by
  the staging operator and loaded through the environment secret manager;
- the database, cache, queue, filesystem, and log configuration is staging-only;
- PII encryption/blind-index key names and versions are available without
  printing key values;
- Midtrans, WhatsApp, and FCM are disabled or explicitly non-production;
- a synthetic active member and a synthetic inactive member exist;
- the health endpoint is reachable from the operator host;
- the operator has an approved recovery command if the application remains in
  maintenance mode.

If any precondition is missing, stop and record the missing owner or credential
name. Do not substitute production credentials or a shared development database.

## Exact-SHA preflight

The deployment workflow must resolve the requested ref to a complete commit SHA.
After deployment, run the checker from the deployed worktree:

```bash
bin/staging-preflight.sh \
  --sha "$DEPLOYMENT_SHA" \
  --health-url "$STAGING_HEALTH_URL"
```

For a strict production-shaped staging configuration, add
`--strict-production`. The checker reports only check names and `PASS`/`FAIL`;
it does not print configuration values or health response bodies. It verifies:

- exact checked-out SHA, clean worktree, clean index, and no untracked files;
- release preflight configuration;
- migration status visibility;
- queue backend command availability;
- registered scheduler tasks;
- storage directory and write permission;
- health response JSON with `status=ok`.

This checker does not prove that a queue worker process or scheduler daemon is
running. Those require the host supervisor evidence described below.

## Host operation checks

Run these read-only checks on the staging host and retain redacted output:

```bash
systemctl is-active kojaya-worker
systemctl is-active cron
php artisan schedule:list
php artisan queue:failed
php artisan migrate:status --no-interaction
```

Use the actual approved service names if the host uses Supervisor rather than
systemd. A process status is not evidence of successful job processing; retain
one synthetic queue execution and scheduler heartbeat record separately.

## Safe smoke sequence

Use a synthetic active member and isolated catalog/payment data. The smoke run
must be read-only until the explicitly approved non-production checkout stage.

1. `POST /api/auth/login`; verify a synthetic session/token is issued.
2. `GET /api/auth/session` and `GET /api/user`; verify the member identity is
   the synthetic account.
3. `GET /api/v1/member/profile` and `GET /api/v1/member/dashboard`.
4. `GET /api/v1/member/savings/summary`,
   `GET /api/v1/member/savings/ledger`, and
   `GET /api/v1/member/dues/invoices`.
5. `GET /api/v1/member/loans` and the documented calculator path.
6. `GET /api/v1/member/coffee/menu` and
   `GET /api/v1/member/store/catalog`.
7. Submit one multi-item coffee or store order only with the approved fake or
   sandbox provider, a fresh synthetic `client_reference`, and a recorded
   idempotency result.
8. `GET /api/v1/member/payment-intents/{intent}` and the payment status route;
   verify `PENDING` is rendered without claiming settlement.
9. If separately approved, send one controlled non-production webhook and
   verify idempotent settlement, stock release/consumption, transaction
   history, notification outbox, and audit record.
10. Verify logout and that a repeated submission with the same reference does
    not create a second order or payment intent.

Never call `/api/payments/webhook` with a real provider payload, create a real
charge, send WhatsApp/FCM, or use a production merchant account as part of this
runbook.

## Negative-path checks

Use separate synthetic accounts and redacted resource IDs:

- inactive member attempting dashboard financial/checkout routes is rejected;
- member from organization A requesting organization B data is rejected;
- cross-account payment-intent or order resource access is rejected;
- stale/expired payment intent is rejected without a new charge;
- changed price or insufficient stock is rejected without a duplicate order;
- network retry with the same `client_reference` is idempotent.

Record HTTP status and a redacted error code/message only. Do not record bearer
tokens, raw PII, payment credentials, or full request bodies.

## Evidence record

```text
Environment: staging/<identifier>
Backend SHA: <40-character SHA>
Operator role: <role, no personal secret or token>
Started: <Asia/Jakarta timestamp>
Finished: <Asia/Jakarta timestamp>
Preflight command: bin/staging-preflight.sh --sha <redacted-in-report> --health-url <redacted>
Preflight result: PASS/FAIL
Health result: PASS/FAIL
Queue worker evidence: <artifact reference>
Scheduler evidence: <artifact reference>
Smoke result: PASS/FAIL/BLOCKED
Negative-path result: PASS/FAIL/BLOCKED
Payment mode: disabled/fake/sandbox (never production)
Artifact reference: <redacted log or CI artifact>
Remaining incident or recovery action: <text>
```

## Current acceptance status

- Exact-SHA checker: implementation prepared; staging execution pending.
- Database/cache/queue/scheduler/storage/health preflight: implementation and
  runbook prepared; environment execution pending.
- Member smoke and negative paths: route contract identified; synthetic staging
  execution pending.
- Deployment success, maintenance exit, and failure rehearsal: not claimed.
