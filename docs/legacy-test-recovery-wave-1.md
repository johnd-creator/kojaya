# Legacy ERP Test Recovery — Wave 1

Status: bounded recovery wave prepared; the full Legacy ERP quarantine remains
in place until additional waves are reviewed.

## Baseline

Audit date: 2026-07-18 (Asia/Jakarta)

| Measure | Evidence |
| --- | --- |
| Legacy ERP files | 42 PHP files under `tests/Feature/LegacyErp` |
| Static `test_` methods | 138, counted with `rg -n "function test_" tests/Feature/LegacyErp` |
| Default-suite state | `phpunit.xml` excludes `tests/Feature/LegacyErp` |
| Previous full-suite result | User evidence: 1286 passed, 5 skipped, 7058 assertions |
| Current main baseline | `9cc26567bbac9f91e6ae5fe791573ea83e166666` |

The static method count is a reproducible inventory, not an assertion that all
methods are independent PHPUnit test cases. Attribute-based tests and helper
methods require a later source-aware inventory.

## Selected wave

Wave 1 targets authorization/organization ownership and finance/import flows.
The files remain in their historical directory and are executed explicitly by
the existing `PHPUnit Parallel` CI job. This keeps the broad Legacy ERP
quarantine unchanged while making the selected recovery gate visible and
blocking when it fails.

| File | Risk addressed | Local result | Assertions |
| --- | --- | ---: | ---: |
| `tests/Feature/LegacyErp/EmployeeScopeTest.php` | Organization ownership and scoped actor isolation | 11 passed | 20 |
| `tests/Feature/LegacyErp/BudgetImportTest.php` | Finance budget validation and import boundary | 2 passed | 7 |
| `tests/Feature/LegacyErp/BankIntegrationTest.php` | Bank transfer export and invoice reconciliation | 1 passed | 6 |
| `tests/Feature/LegacyErp/InvoiceFlowTest.php` | Invoice lifecycle and payment-side finance flow | 12 passed | 26 |
| **Wave total** |  | **26 passed** | **59** |

### Verification commands

These commands use the PHPUnit testing configuration and do not target the
shared development database:

```bash
php artisan test --compact tests/Feature/LegacyErp/EmployeeScopeTest.php
php artisan test --compact tests/Feature/LegacyErp/BudgetImportTest.php
php artisan test --compact tests/Feature/LegacyErp/BankIntegrationTest.php
php artisan test --compact tests/Feature/LegacyErp/InvoiceFlowTest.php
```

The corresponding CI step is:

```text
Legacy ERP Recovery Wave 1
```

inside the existing `PHPUnit Parallel` job. The local results above are model
verification evidence on the isolated PHPUnit database. Main-branch acceptance
also requires the GitHub Actions run for the PR to pass.

## Five skipped tests register

The previous full-suite evidence reports five skips. Their current source
conditions, owners, and recovery gates are recorded below; no skip is removed
in this wave.

| Source | Reason | Owner role | Recovery condition | Target milestone | Decision |
| --- | --- | --- | --- | --- | --- |
| `tests/Feature/P1ArchitectureTest.php::test_api_user_endpoint_is_rate_limited` | Rate-limit behavior is parked with ERP-era infrastructure | Backend maintainer | Rate-limit configuration and deployment topology are confirmed | Legacy recovery Wave 2 | Retain |
| `tests/Feature/Cooperative/MemberLifecycleConcurrencyTest.php` | Requires isolated PostgreSQL concurrency execution | Operations owner | PostgreSQL CI/staging database is available and the dedicated suite is green | PostgreSQL recovery milestone | Retain |
| `tests/Unit/PosSprint6OpenApiContractTest.php::test_openapi_pos_returns_payload_requires_pos_transaction_id` | Payment gateway/OpenAPI workstream is parked | API maintainer | Payment gateway contract is approved and OpenAPI drift is regenerated/verified | Payment contract milestone | Retain |
| `tests/Unit/PosSprint6OpenApiContractTest.php::test_openapi_pos_sync_enqueue_schema_lists_supported_endpoint` | Payment gateway/OpenAPI workstream is parked | API maintainer | Same as above, with endpoint schema evidence | Payment contract milestone | Retain |
| `tests/Feature/Phase4Phase5OperatorHardeningTest.php::test_member_can_register_push_token_create_charge_and_receive_webhook_notification` | Midtrans activation and live provider validation are pending | Payment/integration owner | Approved sandbox credentials and redacted webhook evidence exist | Integration validation milestone | Retain |

Other tests contain conditional skip branches for optional local features (for
example two-factor authentication and frontend source availability). They are
not counted as the five baseline skips unless the current suite executes those
branches. Their conditions should be re-audited when the related feature is
enabled.

## Wave acceptance and next wave

Wave 1 may be marked passed only when the PR's required CI run reports the
`PHPUnit Parallel` job green, including the named recovery step. That result
does not authorize removing the remaining exclusion or claim all Legacy ERP is
production-ready.

Wave 2 should be selected from the remaining authorization, ownership,
inventory/reservation, PII, and payment lifecycle files after failure taxonomy
and owner assignment are reviewed. Any production-code change must be tied to
a reproducible failing test and reviewed separately.

## Safety boundaries

- No test assertion was removed or weakened.
- No skipped test was added or changed.
- No `phpunit.xml` quarantine was removed.
- No production database, payment provider, notification provider, or customer
  data is used by this recovery record.
