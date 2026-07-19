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
| Current integrated main baseline | `50835dbfa693f4edc0a7e7a625fe5936dcb6da0a` |

The static method count is a reproducible inventory, not an assertion that all
methods are independent PHPUnit test cases. Attribute-based tests and helper
methods require a later source-aware inventory.

The `138` count uses the literal `function test_` search. It differs from the
release-readiness estimate of approximately 161 total methods because that
estimate uses a different counting definition and source inventory.

## Provenance

- PR #14 CI classifier hardening is merged into `main`.
- PR #15 ops/security changes are merged into `main`.
- Main post-merge CI run #126 (`29677787436`) completed successfully with all
  nine jobs green.
- Current integrated main SHA: `50835dbfa693f4edc0a7e7a625fe5936dcb6da0a`.
- Integration merge commit: `1be9e2c1cefbf896863c280bce3deff84fbdccd7`.
- Branch head after the integration merge: `1be9e2c1cefbf896863c280bce3deff84fbdccd7`.

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

## Full 42-file inventory

The table below is an inventory record, not a fabricated failure report. Module, risk, owner role, target, and fixture fields are planning classifications based on the test filename and require owner confirmation. Except for the four explicitly selected Wave 1 files, the current failure type is `NOT EXECUTED/UNKNOWN` because those files were not run as part of this wave.

| File | Module | Reason excluded | Current failure type | Production risk | Owner role | Target milestone/version | Required fixture/architecture change | Disposition |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `tests/Feature/LegacyErp/AssetManagementTest.php` | Asset management | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/AttendanceManagementTest.php` | HR / attendance | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/AutomateMaintenanceTest.php` | Maintenance automation | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Operations maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/BankIntegrationTest.php` | Finance / bank integration | Quarantine retained; selected Wave 1 | Wave 1 executed: 1 passed / 6 assertions; no failure observed | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/BankStatementReconcilerTest.php` | Finance / reconciliation | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/BudgetControllerTest.php` | Finance / budget | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/BudgetImportTest.php` | Finance / import | Quarantine retained; selected Wave 1 | Wave 1 executed: 2 passed / 7 assertions; no failure observed | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ClientControllerTest.php` | Client / CRM | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | CRM maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EFakturExportTest.php` | Tax / e-Faktur | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance/tax maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EfakturApiTest.php` | Tax / e-Faktur API | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance/tax maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EfakturBatchExportTest.php` | Tax / e-Faktur batch | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance/tax maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EmployeeControllerTest.php` | HR / employee | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EmployeeEssProvisioningTest.php` | HR / employee self-service | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EmployeeScopeTest.php` | Authorization / organization ownership | Quarantine retained; selected Wave 1 | Wave 1 executed: 11 passed / 20 assertions; no failure observed | Incorrect scope, money, stock, or financial records | Backend authorization maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EmployeeTransferTest.php` | HR / employee transfer | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/EssGeofenceTest.php` | HR / ESS geofence | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/HrMasterDataManagementTest.php` | HR / master data | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/InvoiceFlowTest.php` | Finance / invoice lifecycle | Quarantine retained; selected Wave 1 | Wave 1 executed: 12 passed / 26 assertions; no failure observed | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/LeaveManagementTest.php` | HR / leave | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/OvertimeManagementTest.php` | HR / overtime | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/P5EssPortalTest.php` | Mobile / ESS portal | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Member/API contract or access regression | Mobile/API maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/P5FinanceUiTest.php` | Finance / UI | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/PayrollCalculatorTest.php` | Payroll / calculation | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Payroll maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/PayrollPipelineTest.php` | Payroll / pipeline | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Payroll maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/PettyCashManagementTest.php` | Finance / petty cash | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/Phase2EssMobileApiTest.php` | Mobile / ESS API | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Member/API contract or access regression | Mobile/API maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/Phase3TechnicianMobileApiTest.php` | Mobile / technician API | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Member/API contract or access regression | Mobile/API maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProcurementFlowTest.php` | Procurement / workflow | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Procurement maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProcurementWebFlowTest.php` | Procurement / web | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Procurement maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProjectFinanceTest.php` | Project / finance | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Project module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProjectMilestoneTest.php` | Project / milestones | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Project module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProjectResourceTest.php` | Project / resources | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Project module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProjectTeamAvailabilityTest.php` | Project / team availability | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Project module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ProjectTeamTest.php` | Project / team | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Project module maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ReimbursementManagementTest.php` | Finance / reimbursement | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Finance maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/SalaryStructureManagementTest.php` | Payroll / salary structure | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Payroll maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/ShiftRosterManagementTest.php` | HR / shift roster | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/SparePartManagementTest.php` | Inventory / spare parts | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Inventory maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/Sprint3HrPayrollHardeningTest.php` | HR / payroll hardening | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Sensitive workforce data or access regression | HR maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/TechnicianApiTest.php` | Maintenance / technician API | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Member/API contract or access regression | Operations maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/WarehouseManagementTest.php` | Inventory / warehouse | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Incorrect scope, money, stock, or financial records | Inventory maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |
| `tests/Feature/LegacyErp/WorkOrderWebFlowTest.php` | Maintenance / work orders | Default Legacy ERP quarantine; not selected for Wave 1 | NOT EXECUTED/UNKNOWN | Operational workflow regression | Operations maintainer | Legacy recovery Wave 2 risk-ranked review | Source-aware fixture inventory; exact architecture change TBD after execution | Owner decision required: restore/rewrite/replace/retire |

## Skipped-test tracking

Tracking issue: [#18 — test: track baseline skipped tests recovery](https://github.com/johnd-creator/kojaya/issues/18).

The issue contains one checklist item per baseline skipped test, including owner role, reason, target, recovery condition, and evidence requirement. The five skips remain retained; no `phpunit.xml` exclusion or assertion was changed by this inventory update.
