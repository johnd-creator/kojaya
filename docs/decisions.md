# Kojaya ERP - Architecture Decision Records (ADR)

## 🎯 ADR-003: Playwright Chromium sebagai Web UI Audit Foundation

**Status:** ✅ Accepted
**Date:** July 22, 2026
**Deciders:** Development Team

### Context

Kojaya membutuhkan pemeriksaan visual full-screen, accessibility, dan runtime pada browser nyata yang dapat direview bersama artifact CI. Unit test dan screenshot comparison saja tidak cukup untuk memvalidasi alur Inertia/Vue yang sudah ter-render.

### Decision

Gunakan Playwright Test dengan Chromium, `@axe-core/playwright`, environment/database terisolasi, seeder deterministik, storage state per role, dan manifest screenshot sebagai fondasi audit web. Storybook dan Chromatic ditunda ke fase terpisah.

### Consequences

- Baseline dibuat dan direview pada Linux CI; CI tidak pernah memperbarui baseline otomatis.
- Artifact menyatukan screenshot, visual diff, axe JSON, runtime report, dan trace untuk audit UX per-screen.
- Temuan UX/accessibility dicatat terpisah dan tidak mengubah business logic pada foundation PR.

## ADR-UI-001: Inventory-driven web visual audit

**Status:** Accepted
**Date:** July 23, 2026

The Playwright audit uses one machine-readable registry for route ownership,
fixtures, role/auth state, viewport policy, accessibility, manifest, and
baseline validation. Every renderable cooperative/member GET route must be
audited or have a specific non-visual exclusion. Chromium on Ubuntu 24.04,
fixed Laravel/JavaScript time, deterministic seeded data, and no server reuse
are the rendering contract. This prevents a new page from silently bypassing
visual review while keeping UX findings separate from business logic changes.

## ADR-UI-002: Reproducible audit font loading and accessibility scope

**Status:** Accepted
**Date:** July 23, 2026

The audit intercepts the application's Instrument Sans stylesheet and serves
the reviewed weights from committed test assets. This keeps the production
font unchanged while removing CDN availability and font-cache differences from
visual comparison. Accessibility debt fingerprints are enforced on the
mandatory desktop surface; responsive projects remain visual-policy checks
until viewport-specific accessibility fingerprints are intentionally reviewed.

## 📋 What is ADR?

Architecture Decision Records (ADR) document important architectural decisions made during the project. Each ADR captures:
- **Context** - Why was this decision needed?
- **Decision** - What did we decide?
- **Consequences** - What does this mean for the project?

---

## 🎯 ADR-001: Laravel 12 vs Laravel 11

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Project needed a modern PHP framework with long-term support and latest features. Options were:
- Laravel 11 (stable, proven)
- Laravel 12 (latest, cutting-edge)

### Decision
**Chose Laravel 12** (latest version as of February 2026)

**Rationale:**
- Latest PHP 8.2+ features (constructor promotion, readonly properties, enums)
- Modernized structure (no `app/Http/Kernel.php`, middleware in `bootstrap/app.php`)
- Better performance with Laravel Boost
- Future-proof with latest LTS support
- Access to new features (improved queue system, better error handling)

**Consequences:**

**Positive:**
- Modern PHP features improve code quality
- Better performance with Boost
- Smoother upgrade path for next 2-3 years
- Access to latest Laravel ecosystem

**Negative:**
- Less community knowledge/experience available
- Some packages may not be compatible yet
- Debugging resources more limited

---

## 🎯 ADR-002: Vue 3 + Inertia.js vs Filament PHP

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Admin panel needed for ERP system. Options were:
- Filament PHP v3 (admin panel framework)
- Vue 3 + Inertia.js (custom SPA)

### Decision
**Chose Vue 3 + Inertia.js** (custom SPA)

**Rationale:**
- More flexibility for custom UX requirements
- Better mobile responsiveness (important for future mobile apps)
- Reusable Vue components across web and mobile
- TypeScript support for type safety
- Better developer experience (hot reload, fast builds)
- Indonesian compliance requires highly custom UIs

**Trade-offs:**

**Positive:**
- Custom UX tailored to Indonesian cooperative needs
- Shared components between web and future mobile apps
- Better mobile/responsive design
- TypeScript catches bugs at compile time
- Modern development workflow (HMR, fast builds)

**Negative:**
- Initial development slower (no CRUD auto-generation)
- More code to maintain
- Need to build common UI components from scratch
- 2-3 months additional development time

**Mitigation:**
- Use Reka UI (headless components) to speed up development
- Use shadcn-vue for pre-built components
- Create internal component library
- Document patterns and reuse heavily

---

## 🎯 ADR-003: PostgreSQL vs MySQL

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Primary database choice for ERP system. Options:
- MySQL 8.0 (most common for Laravel)
- PostgreSQL 13+ (enterprise features)

### Decision
**Chose PostgreSQL** as primary database

**Rationale:**
- Better for complex queries (JOINs, subqueries)
- Superior JSON support (for flexible schema)
- Better performance at scale
- More reliable transaction handling
- Better indexing options
- Full-text search built-in
- Easier to migrate to cloud providers (AWS RDS, Google Cloud SQL)

**Consequences:**

**Positive:**
- Better query performance for complex reports
- JSON fields for flexible data (audit logs, metadata)
- More reliable for high-traffic scenarios
- Easier scaling path

**Negative:**
- Slightly higher learning curve
- Some Laravel packages optimized for MySQL
- Hosting costs slightly higher

---

## 🎯 ADR-004: UUID vs Auto-Increment IDs

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Primary key strategy for all database tables. Options:
- Auto-increment integers (Laravel default)
- UUID (Universally Unique Identifiers)

### Decision
**Chose UUID primary keys** for all 72 models

**Rationale:**
- **Security:** Non-guessable IDs prevent enumeration attacks
- **Distributed Systems:** Easier to merge data from multiple sources
- **API-First:** Safe to expose UUIDs in API endpoints
- **Multi-tenancy:** Safer for shared databases
- **Privacy:** Harder to guess record counts (e.g., employee count)

**Implementation:**
```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasFactory, HasUuids;
    // UUID primary key is automatic
}
```

**Consequences:**

**Positive:**
- Improved security posture
- Safe API endpoints (no ID enumeration)
- Better support for distributed systems
- Easier data migration/merging

**Negative:**
- Larger indexes (UUIDs are 36 chars vs integers)
- Slightly slower JOINs
- Can't sort by ID to get insertion order
- More complex debugging (human-unfriendly IDs)

**Mitigation:**
- Use `created_at` timestamp for insertion order
- Optimize indexes for UUID columns
- Add `ulid` or `ordered_uuid` for sortable IDs (future)

---

## 🎯 ADR-005: Sanctum vs Passport for API Auth

**Status:** ✅ Accepted
**Date:** March 1, 2026
**Deciders:** Development Team

### Context
API authentication for mobile apps. Options:
- Laravel Passport (OAuth2)
- Laravel Sanctum (API tokens)

### Decision
**Chose Laravel Sanctum** for API authentication

**Rationale:**
- **Simpler:** No need for full OAuth2 complexity
- **Lighter:** Smaller footprint, faster performance
- **Mobile-friendly:** Token-based auth perfect for mobile apps
- **SPA-support:** Can handle both token and session auth
- **Easier integration:** Simpler for mobile developers

**Implementation:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
```

**Consequences:**

**Positive:**
- Simple token generation and management
- Good performance
- Easy for mobile developers to use
- Supports both token and session auth

**Negative:**
- Tokens don't expire by default (security concern)
- No OAuth2 benefits (refresh tokens, scopes)
- Manual token management required

**Mitigation:**
- Add token expiration in v1.1
- Implement token rotation
- Add token abilities (permissions)
- Monitor token usage

---

## 🎯 ADR-015: Permission-Based Authorization Over Controller Role Checks

**Status:** ✅ Accepted
**Date:** May 16, 2026
**Deciders:** Engineering

### Context
Several controller paths still encoded access decisions directly with role checks or scattered permission checks. Mobile token abilities were also mapped inside `AuthController`, which made future permission changes harder to audit.

### Decision
Move domain authorization into policies and move Sanctum ability mapping into `TokenAbilityResolver`, based primarily on Spatie permissions and model relationships.

### Consequences

**Positive:**
- Controllers use policy methods for cooperative and organization decisions.
- Loan actions are auditable through `LoanPolicy`.
- Mobile token behavior can be tested independently from `AuthController`.
- Project Gantt dependencies now use an Eloquent model instead of raw table writes.

**Trade-off:**
- Role seeders must keep permission assignments accurate because token abilities now derive from permission state.

---

## 🎯 ADR-016: Member API Responses Use Allowlisted Resources

**Status:** ✅ Accepted
**Date:** May 17, 2026
**Deciders:** Engineering

### Context
Kojayaku member endpoints returned several Eloquent models and paginators directly. Laravel model `$hidden` protected sensitive user credentials, but raw serialization still exposed internal columns and made the mobile contract drift whenever models changed.

### Decision
Member-facing API responses must use allowlisted Resources/DTO-style payloads for user, member, invoice, payment, loan, restructure, withdrawal, notification, and support ticket responses.

### Consequences

**Positive:**
- Mobile clients receive a stable response contract.
- Sensitive/internal model fields are not exposed by accident.
- Future model columns do not become public API fields automatically.

**Trade-off:**
- Existing mobile clients must consume the explicit field names rather than raw database foreign-key fields.

---

## 🎯 ADR-006: Service Layer Pattern

**Status:** ✅ Accepted
**Date:** March 5, 2026
**Deciders:** Development Team

### Context
Business logic organization approach. Options:
- Fat Controllers (logic in controllers)
- Service Layer (separate business logic classes)
- Domain-Driven Design (complex)

### Decision
**Chose Service Layer Pattern**

**Rationale:**
- **Separation of Concerns:** Controllers handle HTTP, Services handle logic
- **Reusability:** Services can be reused across controllers
- **Testability:** Easier to unit test services
- **Maintainability:** Business logic in one place
- **Clarity:** Clear distinction between HTTP and business logic

**Implementation:**
```php
// Controller
class PayrollController extends Controller
{
    public function generate(GeneratePayrollRequest $request)
    {
        $payroll = $this->service->calculate(
            $request->validated(),
            $request->user()
        );
        return new PayrollResource($payroll);
    }
}

// Service
class PayrollCalculatorService
{
    public function calculate(array $data, User $user): Payroll
    {
        // Complex calculation logic
    }
}
```

**Examples:**
- `PayrollCalculatorService` - Payroll calculations
- `Pph21TerService` - Tax calculations
- `BpjsCalculationService` - BPJS calculations
- `NotificationService` - Notifications
- `AuditLogService` - Audit logging

**Consequences:**

**Positive:**
- Clean, focused controllers
- Reusable business logic
- Easier to test services
- Single source of truth for calculations

**Negative:**
- More files to maintain
- Indirection can be confusing initially
- Need to understand service layer pattern

---

## 🎯 ADR-007: API Versioning Strategy

**Status:** ✅ Accepted
**Date:** March 15, 2026
**Deciders:** Development Team

### Context
API versioning approach for mobile apps. Options:
- URL-based versioning (`/api/v1/resource`)
- Header-based versioning (`Accept: application/vnd.api.v1+json`)
- No versioning (breaking changes require new API)

### Decision
**Chose URL-based versioning** with path prefix

**Rationale:**
- **Explicit:** Version visible in URL
- **Simple:** Easy to implement and understand
- **Cachable:** Different versions can have different cache keys
- **Clear:** Mobile developers know which version they're using

**Implementation:**
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::get('/members', [CooperativeMemberApiController::class, 'index']);
    Route::post('/members', [CooperativeMemberApiController::class, 'store']);
});
```

**Consequences:**

**Positive:**
- Clear version separation
- Easy to deprecate old versions
- Better cache control
- Simpler for mobile developers

**Negative:**
- Need to maintain multiple versions (eventually)
- Route definitions more verbose

**Future:**
- Add header-based versioning for internal APIs
- Consider GraphQL for flexible versioning

---

## 🎯 ADR-008: Soft Deletes vs Hard Deletes

**Status:** ✅ Accepted
**Date:** March 20, 2026
**Deciders:** Development Team

### Context
Data deletion strategy. Options:
- Hard deletes (permanently remove data)
- Soft deletes (mark as deleted)

### Decision
**Chose Soft Deletes** for most models

**Rationale:**
- **Audit Trail:** Preserve data for compliance
- **Recoverability:** Can restore accidentally deleted records
- **Reporting:** Historical reports need old data
- **Compliance:** Indonesian regulations require data retention

**Implementation:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    // deleted_at column added automatically
}
```

**Consequences:**
- Need to query with `whereNull('deleted_at')` or use `withTrashed()`
- Database grows larger over time
- Need cleanup jobs for old deleted records

---

## 🎯 ADR-009: TypeScript vs JavaScript

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Frontend language choice. Options:
- JavaScript (simpler, faster)
- TypeScript (type-safe)

### Decision
**Chose TypeScript with strict mode**

**Rationale:**
- **Type Safety:** Catch bugs at compile time, not runtime
- **Better IDE Support:** Autocompletion, refactoring tools
- **Self-Documenting:** Types act as inline documentation
- **Team Scale:** More developers = more value from types
- **Confidence:** Fewer runtime errors in production

**Consequences:**

**Positive:**
- Fewer runtime errors
- Better developer experience
- Safer refactoring
- Self-documenting code

**Negative:**
- Initial setup more complex
- Learning curve for JavaScript developers
- Compilation step required
- Type definitions need maintenance

---

## 🎯 ADR-010: Tailwind CSS v4 vs v3

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
CSS framework version choice. Options:
- Tailwind CSS v3 (stable, proven)
- Tailwind CSS v4 (latest, beta)

### Decision
**Chose Tailwind CSS v4** (latest version as of Feb 2026)

**Rationale:**
- **New Features:** Native CSS nesting, @layer support
- **Better Performance:** Oxide compiler (Rust-based, 10x faster)
- **Smaller Bundle Size:** Tree-shaking of unused utilities
- **Future-Proof:** Latest features and improvements

**Consequences:**

**Positive:**
- Faster build times
- Smaller CSS bundles
- Modern CSS features
- Better developer experience

**Negative:**
- Beta software (potential bugs)
- Less documentation/examples available
- Some plugins may not be compatible yet

---

## 🎯 ADR-016: Transactional Notification Outbox and CI Contract Gates

**Status:** ✅ Accepted
**Date:** May 16, 2026
**Deciders:** Engineering

### Context
Notification delivery and API contract drift were still operational risks. Push delivery failures were only logged, OpenAPI snapshots lived outside the repository, and CI did not enforce an explicit coverage threshold.

### Decision
Use a database-backed transactional notification outbox with a scheduled retry command, bind swappable service contracts through `App\Contracts`, version `docs/openapi.json`, and enforce OpenAPI drift plus parallel coverage gates in CI.

### Consequences

**Positive:**
- Notification delivery can retry independently from domain transactions.
- Failed notification outbox rows are visible in health and metrics dashboards.
- Mobile API contract changes are reviewed through `docs/openapi.json`.
- CI blocks pull requests that drift the API snapshot or drop coverage below the agreed threshold.

**Trade-off:**
- The scheduler and queue worker must be active in production for retry latency to stay near the configured interval.

---

## 🎯 ADR-017: Production Operations Automation

**Status:** ✅ Accepted
**Date:** May 17, 2026
**Deciders:** Engineering

### Context
Production readiness still needed explicit retention, backup, error traceability, and a repeatable deployment entrypoint. Without these, operators would rely on manual cleanup, manual database exports, and ad hoc SSH deployment steps.

### Decision
Add scheduled Laravel commands for operational retention and database backups, include `request_id` in API error envelopes using the existing correlation id middleware, and provide a manual GitHub Actions deployment workflow backed by `bin/deploy.sh`.

### Consequences

**Positive:**
- Log and audit retention are configurable through environment variables.
- Database backups can run from the scheduler and prune old artifacts.
- API errors can be correlated with logs using `request_id` / `X-Correlation-ID`.
- Deployment has a repeatable command sequence for dependencies, build, migration, cache optimization, and queue restart.

**Trade-off:**
- Production still needs external storage, scheduler, SSH secrets, and backup restore drills configured outside the application repository.

---

## 🎯 ADR-018: Payroll Generation Boundary and Payroll Status Enums

**Status:** ✅ Accepted
**Date:** June 3, 2026
**Deciders:** Engineering

### Context
M3 P0 review found payroll generation orchestration living directly in `PayrollController::generate`, plus repeated payroll status strings across controller/model/service paths. Payroll generation is financial domain behavior and needs stronger transactional and testing boundaries.

### Decision
Move regular payroll generation into `PayrollGenerationService`, keep controller responsibilities limited to HTTP validation/redirects, wrap generation in a database transaction, and use payroll-domain enums for touched status values.

### Consequences

**Positive:**
- Payroll generation is easier to test independently from Inertia routing.
- Duplicate generation for the same employee and period remains idempotent.
- Payroll status values have a typed source of truth in `PayrollStatus` and `PayrollApprovalStatus`.
- Future payroll generation variants can reuse the service boundary.

**Trade-off:**
- Existing model status casts remain string-based for compatibility, so enum adoption is incremental rather than complete.

---

## 🎯 ADR-019: Idempotency Keys and API Timing Headers

**Status:** ✅ Accepted
**Date:** June 3, 2026
**Deciders:** Engineering

### Context
Mobile clients can retry financial write requests during unstable connections. Without idempotency, repeated loan applications, payment proof uploads, POS transactions, or payment charges can create duplicate records. Architecture targets also cite p95 latency, but API responses did not expose request duration.

### Decision
Selected financial write API routes use an `Idempotency-Key` header through `EnsureIdempotentWrite`. Matching repeat requests replay the original JSON response, while the same key with a different payload returns `409 CONFLICT`. API responses also include `X-Response-Time-Ms` and log timing metadata with request id, method, route, status, and duration.

### Consequences

**Positive:**
- Mobile retries can be made safely for covered financial write operations.
- Duplicate writes are prevented without changing existing controller/service contracts.
- API latency can be collected from headers/logs and aligned with the p95 target.

**Trade-off:**
- Idempotency state currently uses cache storage with a 24-hour retention window, so production cache persistence affects replay availability.

---

## 🎯 ADR-020: Tax Rules as Data and P2 Smoke Coverage

**Status:** ✅ Accepted
**Date:** June 3, 2026
**Deciders:** Engineering

### Context
M3 P2 review noted that PPh21 parameters should not remain permanent code constants because PTKP values, progressive layers, effective periods, and regulation references can change. P2 also called for early static analysis and smoke coverage, but PHPStan/Larastan and browser E2E runners are not installed in the project dependencies.

### Decision
Store PPh21 rule parameters in `tax_rules` with effective date windows and seed the current default rule. `Pph21TerService` resolves the active rule for the requested payroll period and falls back to the default rule if the table is not available yet. Add `phpstan.neon` as the initial static analysis config, while keeping dependency installation pending explicit approval. Use PHPUnit smoke coverage for the member loan API to cooperative admin review path until a browser E2E runner is approved.

### Consequences

**Positive:**
- Payroll tax calculation can adapt to regulation changes through data and seed updates.
- Payroll calculation records expose the tax rule code/reference used by the service.
- P2 smoke coverage protects a high-value mobile-to-admin cooperative flow without adding dependencies.
- Static analysis has a baseline config ready for Larastan/PHPStan adoption.

**Trade-off:**
- Static analysis cannot run in CI until PHPStan/Larastan is added to `require-dev`.
- Browser-level E2E remains pending because no browser test runner is installed.

---

## 🔮 Future Decisions (Pending)

### ADR-011: Payment Gateway Provider
**Status:** ⏳ Pending (Phase 4)
**Options:** Midtrans vs Xendit vs Stripe
**Timeline:** July 2026

### ADR-012: Mobile App Framework
**Status:** ⏳ Pending (Phase 3)
**Options:** Flutter vs React Native vs Kotlin Native
**Timeline:** May 2026

### ADR-013: Real-time Updates
**Status:** ⏳ Pending (Phase 5)
**Options:** WebSocket vs Server-Sent Events vs Polling
**Timeline:** Q4 2026

### ADR-014: Caching Strategy
**Status:** ⏳ Pending
**Options:** Redis vs Memcached vs Laravel Cache
**Timeline:** When traffic increases

---

## 📊 Decision Template

For future architectural decisions, use this template:

```markdown
## ADR-XXX: [Decision Title]

**Status:** ⏳ Proposed | ✅ Accepted | ❌ Rejected
**Date:** [Date]
**Deciders:** [Who made the decision]

### Context
[What is the issue or problem?]
[What are the constraints?]
[What are the options?]

### Decision
[What did we decide?]
[Why did we choose this option?]

### Consequences
**Positive:**
- [Benefit 1]
- [Benefit 2]

**Negative:**
- [Drawback 1]
- [Drawback 2]

**Mitigation:**
- [How we address the drawbacks]
```

---

## 🔄 Decision Review Process

### **When to Review Decisions:**
- Every 6 months
- Major technology changes
- Performance issues
- Security concerns
- Team feedback

### **How to Reverse Decisions:**
1. Document reasons for reversal
2. Get team consensus
3. Create migration plan
4. Update ADR status
5. Implement changes

---

## 🎯 ADR-021: Google SSO dengan Socialite, Social Accounts, dan Admin Validation

**Status:** ✅ Accepted
**Date:** June 7, 2026
**Deciders:** Engineering

### Context
Kojayaku butuh login yang familiar untuk anggota tanpa password baru, tetapi tidak boleh otomatis menganggap semua pengguna Google sebagai anggota aktif. Tanpa kontrol yang jelas, akun Gmail yang salah ketik atau duplikat bisa membuat akses anggota yang sensitif terbuka sebelum Admin Koperasi memvalidasi data.

### Decision
- Pakai `laravel/socialite` untuk Google OAuth.
- Simpan identitas provider di tabel `social_accounts` (bukan kolom di `users`) untuk konsistensi multi-provider di masa depan.
- `cooperative_members` memakai `validation_status` dengan nilai `PENDING`, `PENDING_VALIDATION`, `ACTIVE`, `REJECTED`, `REVISION`, field final approval `validated_at`, `validated_by`, `validation_notes`, serta field verifikasi Admin Koperasi `admin_validated_at`, `admin_validated_by`, `admin_validation_notes`.
- Validasi calon anggota memakai dua tahap: Admin Koperasi melakukan verifikasi awal, lalu Pengurus Koperasi atau System Admin melakukan approval final sebelum role `Anggota` diberikan.
- Email Google dicocokkan lowercase, hanya untuk email dengan `email_verified = true`. Konflik provider_id ditolak dengan audit event.
- Redirect pasca-login dan pasca-onboarding mengikuti aturan prioritas di `docs/google_sso.md` Bagian 9.
- Aktifkan Google SSO hanya bila `GOOGLE_SSO_ENABLED=true` agar rollout aman.

### Consequences

**Positive:**
- Login anggota familiar dan cepat, mengurangi beban admin untuk pembuatan akun manual.
- Tabel `social_accounts` siap jika nanti ada provider lain tanpa migrasi besar.
- Validasi berlapis membuat Admin Koperasi dapat menyaring data awal sementara Pengurus Koperasi/System Admin menjadi gate akhir sebelum akses fitur finansial.
- Audit log memberi jejak untuk setiap login, linking, dan konflik.

**Trade-off:**
- Tambah satu paket Composer (`laravel/socialite`) ke dependency.
- Perlu update `Login.vue` dan `MemberPortalController` agar konsisten dengan aturan redirect baru.
- Rollout produksi harus bertahap lewat `GOOGLE_SSO_ENABLED` agar admin siap memvalidasi calon anggota baru.

---

## 🎯 ADR-007: POS Platform Foundation (Phase 0–6)

**Status:** ✅ Accepted
**Date:** June 13, 2026
**Deciders:** Development Team

### Context
Koperasi membutuhkan ekosistem POS yang tidak hanya memproses penjualan, tetapi juga mendukung split payment, retur, void dengan persetujuan, kredit anggota, multi-location inventory, laporan, shift kasir, dan kemampuan offline.

### Decision
Kami membangun **POS 6 fase** yang masing-masing berdiri sendiri tapi saling terintegrasi:

- **Phase 0 – Polishing**: gambar produk, diskon per item, validasi qty, kembalian tunai, dan cetak receipt.
- **Phase 1 – Operational Hardening**: split payment, retur (restock + refund), void request dengan approval berjenjang (`pos_void_requests`), serta filter histori transaksi.
- **Phase 2 – Member Engagement**: kredit anggota (`cooperative_members.outstanding_balance`/`credit_limit`/`credit_term_days`), pembayaran cicilan anggota (`pos_member_credit_payments`), poin otomatis per transaksi, dan visibilitas transaksi khusus anggota via API member-self-service.
- **Phase 3 – Multi-Location Inventory**: lokasi stok (`pos_inventory_locations`), stok per lokasi (`pos_inventory_stocks`), penerimaan barang (RCP), transfer antar lokasi (TRF), dan stock opname (OPC) dengan alur draft → review → approved.
- **Phase 4 – Reporting & Analytics**: laporan harian/bulanan dengan filter produk/kasir/metode/payment, top produk/anggota/kasir, tren harian, dan ekspor CSV serta PDF menggunakan DomPDF.
- **Phase 5 – Shift, Closing & Journals**: shift kasir (`pos_cashier_shifts`) dengan `expected_cash` vs `closing_cash`, daily closing (`pos_daily_closings`) yang mengunci hari, dan auto-posting ke `cooperative_ledger_entries` (POS_SALE/POS_COGS/POS_RETURN/POS_MEMBER_CREDIT/POS_SHIFT_DIFF/POS_DAILY_CLOSING).
- **Phase 6 – Offline Sync**: queue idempotent (`pos_sync_requests` dengan `idempotency_key` UNIQUE), endpoint `/api/v1/pos/sync/{catalog,enqueue,process,batch,status}`, replay response untuk sync duplikat, dan client-side `useOfflinePos` composable yang memanfaatkan localStorage + backoff.

### Consequences

**Positive:**
- POS siap untuk koperasi dengan banyak toko, kasir shift, dan keterbatasan jaringan.
- Idempotency key memastikan penjualan offline yang disinkronkan ulang tidak membuat transaksi ganda.
- Laporan dan jurnal koperasi terotomasi, mengurangi rekonsiliasi manual.
- Member credit + poin terikat langsung ke API member-self-service, sehingga anggota Kojayaku bisa melihat status sendiri.

**Trade-off:**
- Penambahan banyak tabel baru membutuhkan migrasi bertahap (lihat `database/migrations/2026_06_13_*`).
- Fitur offline mengandalkan endpoint RESTful + token, sehingga client perlu login ulang saat online.
- Stock opname butuh peran supervisor (`manage_pos_products`) agar tidak bisa disetujui kasir yang sama.

---

## 🎯 ADR-022: POS Accounting Decision — cooperative_ledger_entries Sebagai Sumber Posting Sementara

**Status:** ✅ Accepted
**Date:** June 13, 2026
**Deciders:** Engineering

### Context

POS Phase 0–6 (lihat ADR-007) mengirim uang, piutang anggota, HPP, retur, dan selisih kas ke `cooperative_ledger_entries`. Audit putaran kedua menemukan tiga keputusan yang harus dibuat eksplisit sebelum POS dipakai operasional:

- `cooperative_ledger_entries` adalah tabel anggota. Bagaimana penjualan non-anggota dicatat tanpa menyalahi model anggota?
- Kontrak lama `POS_RETURN` (`credit = $amount` ke ledger anggota) bentrok dengan kontrak akuntansi baru (debit kontra-revenue). Tidak mungkin memilih keduanya tanpa dokumentasi.
- Void hanya menghapus status transaksi dan me-restore stok, tanpa `*_REVERSAL` entry, sehingga laporan historis tidak bisa membedakan transaksi yang di-cancel dan transaksi yang masih utuh.
- Saat ini, entry `POS_SALE/COGS/MEMBER_CREDIT` hanya credit/debit satu sisi (tidak ada debit cash atau credit persediaan), sehingga belum bisa disebut jurnal akuntansi lengkap.

### Decision

POS tetap memakai `cooperative_ledger_entries` sebagai **sumber posting ledger POS sementara** dengan aturan berikut. ADR ini tidak mengklaim POS sudah menjadi jurnal akuntansi lengkap.

#### 1. `cooperative_member_id` nullable, tidak ada “system member”

- Kolom `cooperative_ledger_entries.cooperative_member_id` dibuat nullable (lihat migrasi `2026_06_13_000008_make_ledger_member_nullable_for_pos`) dengan branch eksplisit untuk SQLite, MySQL/MariaDB, dan PostgreSQL.
- Penjualan, retur, void, dan shift difference untuk non-anggota tetap diposting dengan `cooperative_member_id = null` dan `ledger_scope = 'POS'`. Laporan koperasi memfilter `ledger_scope` sehingga entry tanpa anggota tidak ikut dalam SHU/ledger anggota.
- Tidak ada anggota dummy “system member”. Membuat anggota fiktif akan mengotorkan laporan anggota dan SHU.

#### 2. Kontrak `POS_RETURN`: dual entry

- `POS_RETURN` tetap memakai kontrak lama: `credit = $amount` ke `cooperative_member_id` anggota. Ini konsisten dengan `POS_MEMBER_CREDIT_PAYMENT` dan anggota payment history.
- Tambahan `POS_RETURN_REVERSAL`: `debit = $amount`, `cooperative_member_id = null`. Entry ini adalah kontra-revenue akuntansi agar laporan revenue tidak ikut double-count dengan member credit.
- Migrasi `Sprint2BusinessCriticalFlowsTest` lama yang mengharapkan `credit = 20000` tetap valid. Test baru `PosSprint5JournalConsistencyTest::test_return_posts_credit_to_member_ledger_and_contra_revenue` memvalidasi kedua entry.
- Batasan: `POS_RETURN_REVERSAL` tidak boleh dipakai untuk rekonsiliasi per akun sampai ada chart-of-accounts; untuk saat ini, ini hanya penanda untuk net_sales.

#### 3. Kontrak void: 3 entry `*_REVERSAL`

`PosJournalPostingService::postVoidReversal()` menulis tiga entry untuk satu transaksi void:

| Entry | Member | Debit | Credit | Tujuan |
| --- | --- | --- | --- | --- |
| `POS_SALE_REVERSAL` | anggota/null (menyesuaikan sale asli) | total_amount | 0 | Batalkan `POS_SALE` |
| `POS_COGS_REVERSAL` | null | 0 | snapshot_cogs | Batalkan `POS_COGS` (HPP kembali ke persediaan) |
| `POS_MEMBER_CREDIT_REVERSAL` | anggota | 0 | member_credit_amount | Batalkan piutang anggota + kurangi `outstanding_balance` |

- Semua reversal idempotent per source (lihat `firstOrCreateEntry`), sehingga approve void yang diulang tidak membuat entry ganda.
- `PosTransactionService::approveVoid()` memanggil `postVoidReversal()` dalam transaksi DB yang sama dengan restock dan update status.

#### 4. Batasan scope

- **Bukan** jurnal akuntansi lengkap: tidak ada debit kas/persediaan/piutang ke akun COA eksplisit. POS hanya menandai debit/credit per entry, belum merepresentasikan akun.
- **Bisa direkonsiliasi** untuk: gross sales (POS_SALE), COGS (POS_COGS), piutang anggota (POS_MEMBER_CREDIT), retur anggota (POS_RETURN + POS_RETURN_REVERSAL), void (3 reversal), dan selisih kas (POS_SHIFT_DIFF).
- **Tidak** bisa direkonsiliasi (untuk saat ini): debit kas/QRIS/transfer/bank, credit persediaan dari COGS, dan pencatatan piutang non-anggota.
- COGS memakai snapshot `pos_transaction_items.cost_price` (lihat `PosJournalPostingService::postCogs()`), bukan harga produk saat ini, sehingga laporan historis tidak bergeser saat harga beli berubah.

#### 5. Migrasi data historis

- Tidak ada migrasi data untuk entry `cooperative_ledger_entries` lama. Entry baru POS mengikuti kontrak di atas; entry lama tetap utuh.
- Jika ke depan perlu migrasi debit/credit untuk entry baru (mis. `POS_SALE` jadi debit cash + credit revenue), lakukan ADR lanjutan, bukan update diam-diam.

### Consequences

**Positive:**
- Kontrak `POS_RETURN` jelas dan stabil: `POS_RETURN` untuk ledger anggota, `POS_RETURN_REVERSAL` untuk net_sales. Tidak ada lagi dual-interpretasi antara test lama dan baru.
- Void dapat diaudit: laporan bisa memfilter `*_REVERSAL` untuk membatalkan efek transaksi yang void.
- Penambahan nullable FK tidak mengotorkan data anggota. Tidak ada “system member” palsu.
- Migrasi driver-eksplisit membuat deployment SQLite (test), MySQL/MariaDB, dan PostgreSQL (dev/prod) sama-sama aman.
- COGS snapshot memastikan laporan tidak bergeser saat harga produk berubah.

**Trade-off:**
- Entry POS masih satu sisi debit/credit per row, sehingga belum cukup untuk laporan keuangan formal (neraca, laba rugi per akun).
- Rekonsiliasi kas/QRIS/transfer/persediaan saat ini dilakukan di luar ledger, oleh daily closing.
- Penambahan dua enum entry baru (`POS_RETURN_REVERSAL`, `POS_*_REVERSAL`) menambah cardinality `entry_type`; report builder perlu mengenali prefix `*_REVERSAL` agar tidak ikut di gross_sales.

**Mitigasi:**
- Report builder koperasi perlu mengikuti `ledger_scope = 'POS'` dan skip entry `*_REVERSAL` dari gross_sales (cek `PosSalesReportService`).
- ADR ini akan dievaluasi ulang saat fase accounting penuh dimulai; jika saat itu ada modul chart-of-accounts dedicated, ADR baru akan menambah/menggantikan keputusan ini, bukan tumpang tindih.
- Operational runbook perlu menyebutkan: `cooperative_member_id` boleh null untuk scope POS, dan jangan membuat anggota “system” untuk menutupi ini.

---

## 🎯 ADR-023: Cooperative Notification Activation via Dispatcher (Database Channel)

**Status:** ✅ Accepted
**Date:** July 1, 2026
**Deciders:** Engineering

### Context

The cooperative roles (Anggota, Admin Koperasi, Manajer Koperasi, Pengurus Koperasi) are the core users of KojayaPro/Kojayaku, yet several core workflows emitted no notifications despite a mature dispatcher and bell UI already existing:
- Membership validation/approval (`MemberValidationService`, `MemberOnboardingSubmitService`)
- POS sale & void (`PosTransactionService`) — only coffee orders notified
- Savings withdrawal (`SavingsWithdrawalService`)
- Points earn/redeem/expire & reward redemption status (`PointService`)
- Loan writeOff & restructure (`LoanService::writeOff`, `LoanRestructureService`)

Two `type` strings (`App\Notifications\DatabaseNotification`, `App\Notifications\CooperativeDatabaseNotification`) were written to the `notifications` table without matching class files, and the bell's unread count was not shared via Inertia (requiring an extra XHR on first paint).

### Decision

- Extend the existing `CooperativeNotificationDispatcher` (not Laravel Events — consistent with ADR-016) with domain methods for every uncovered workflow transition, each wrapped in `DB::afterCommit()` and protected by `deduplication_key` for idempotency.
- Inject the dispatcher into the previously-unwired services (`MemberValidationService`, `MemberOnboardingSubmitService`, `PosTransactionService`, `SavingsWithdrawalService`, `PointService`, `LoanRestructureService`).
- Use the **database channel only** (bell) for this activation; email/WhatsApp/FCM outbox enqueue is intentionally NOT added for these events (channel expansion deferred).
- Materialize the two ghost notification classes (`DatabaseNotification`, `CooperativeDatabaseNotification`) so the polymorphic `type` column hydrates cleanly.
- Share `notifications.unreadCount` via `HandleInertiaRequests` (lazy closure) and seed the bell's initial badge from it; accelerate bell polling from 30s to 10s. Real-time push (WebSocket/SSE) remains deferred per ADR-013.

### Consequences

**Positive:**
- Anggota, Admin, Manajer, and Pengurus now receive timely bell notifications for every cooperative transaction transition (approval tasks for staff, status updates for members).
- Bell badge renders instantly on first paint (no empty flash) while still refreshing via 10s polling.
- Deduplication keeps notification volume safe even for high-frequency point earnings from POS.
- No new dependencies introduced.

**Trade-off:**
- 10s polling increases request frequency 3x; acceptable until a real-time layer (ADR-013) is adopted.
- DB-channel only means members without an active web session only see notifications on next login; external channels remain a future activation.

---

## 🎯 ADR-024: Member PII Encryption dan Blind Index Bertahap

**Status:** ✅ Accepted
**Date:** July 11, 2026
**Deciders:** Engineering

### Decision

Member identity number, NPWP, dan nomor rekening ditulis ke kolom encrypted (`*_enc`) dan HMAC blind index (`*_bidx`) untuk data baru. Model tetap melakukan dual-read terhadap kolom plaintext legacy selama backfill, sementara exact-match search memakai blind index dan kolom encrypted disembunyikan dari serialisasi.

Backfill dijalankan eksplisit melalui `members:backfill-sensitive-data --chunk=250`; plaintext legacy tidak dihapus otomatis oleh migration. Penghapusan plaintext memerlukan verifikasi checksum, backup, dan persetujuan deployment terpisah.

Kunci blind index memakai `PII_BLIND_INDEX_KEY` dari secret manager; `APP_KEY` hanya fallback lokal/testing. Hash memasukkan `PII_BLIND_INDEX_VERSION` (default `v1`) agar rotasi key/index dapat dilakukan melalui backfill terkontrol.

### Consequences

- Data baru tidak menyimpan nilai PII plaintext.
- Pencarian exact dapat dilakukan tanpa LIKE terhadap ciphertext.
- Deployment aman terhadap data legacy karena read fallback dan backfill checkpointable.
- Key rotation dan penghapusan kolom legacy tetap menjadi langkah operasional berikutnya.

---

## 🎯 ADR-025: Member Mutation Command Separation dan Legacy Status Preflight

**Status:** ✅ Accepted
**Date:** July 11, 2026
**Deciders:** Engineering

### Decision

Generic member profile update hanya boleh mengubah profile fields non-sensitive. PII write, account linking, dan lifecycle transition memakai action/endpoint terpisah dengan permission atau transition guard masing-masing. Export organisasi non-global tanpa `organization_id` ditolak, bukan diperlakukan sebagai global scope.

Legacy lifecycle rows diaudit melalui `members:audit-status-consistency` dan diperbaiki secara idempoten hanya melalui `members:backfill-status-consistency --apply` setelah backup dan review report.

### Consequences

- Status tidak dapat berubah melalui mass-assignment profile atau API generic update.
- PII yang tidak dikirim tetap preserved; explicit clear hanya tersedia pada dedicated PII action.
- CI tetap gagal pada generated drift, tetapi seluruh evidence checks tetap dieksekusi untuk diagnosis.
- Data legacy yang belum konsisten terlihat sebelum strict active gate ditegakkan.

## 🎯 ADR-026: Document 04 Organization Authorization dan App-Specific Token Cutover

**Status:** ✅ Accepted
**Date:** July 15, 2026
**Deciders:** Engineering

### Context

Review Document 04 menemukan dua jalur operasional yang belum lengkap: member
existing tanpa `user_id` berhenti pada `manual_member_link_required`, dan token
legacy dengan ability wildcard/combined tidak memiliki kontrak rotasi yang
eksplisit. Dokumentasi lama juga masih menyebut wildcard admin sebagai profil
yang didukung.

### Decision

- Account linking memakai endpoint dedicated untuk exact-email candidate lookup
  yang dibatasi ke organization anggota, hanya mengembalikan user terverifikasi
  yang belum tertaut, lalu memakai endpoint link transactional dengan controlled
  reason code. Generic create/edit tetap tidak menerima `user_id`.
- `POST /api/token/rotate` menerima `app` hanya jika token legacy unsafe dan
  membutuhkan salah satu profile `member`, `ess`, `technician`, atau `admin`.
  Token dengan metadata atau legacy profile yang aman wajib mempertahankan
  profile-nya; pemilihan app tidak menambah permission.
- Metadata `token_app` dan `token_version` menjadi bagian dari response rotasi;
  ability baru selalu dihitung dari permission user saat ini. Wildcard tidak
  diterbitkan oleh issuer baru.
- Cooperative tetap menjadi domain aktif Document 04. Role ERP/PT KBU,
  finance/HR workflow, dan Document 05 tetap deferred.

### Consequences

- Operator memiliki jalur aman untuk menyelesaikan existing member tanpa broad
  user directory atau email-only auto-link.
- Unsafe legacy tokens tidak dapat diputar diam-diam ke profile lain dan harus
  melewati keputusan app yang eksplisit.
- Client mobile perlu mengirim `app` hanya saat menerima error bahwa legacy
  token membutuhkan explicit application rotation; client tidak boleh meminta
  wildcard abilities.

---

## 🎯 ADR-027: Document 05 Audit Context, Bounded Pagination, dan Response Contracts

**Status:** READY FOR INDEPENDENT REVIEW
**Date:** July 15, 2026
**Deciders:** Engineering

### Context

Audit events emitted from HTTP requests, queued jobs, scheduled commands, and
domain services could derive actor data from ambient authentication state. API
pagination also had several local parsers with different behavior for malformed
or oversized values, while selected endpoints returned raw Eloquent models.

### Decision

- Audit writes accept an explicit AuditContext containing actor, roles,
  organization, correlation ID, source, and request metadata. Domain and CLI
  operations pass their actor/source explicitly.
- Audit persistence is mandatory for security-sensitive lifecycle events
  (export, PII operations, account linking, token/access changes, and
  financial state transitions). The operation fails or rolls back when its
  required audit write fails. Best-effort telemetry is limited to
  non-authoritative metrics and is not used as the audit record.
- Audit redaction recursively removes sensitive values by field name,
  including nested crypto, token, gateway, authorization, and bank-account
  payloads. Export audit events use requested/completed/failed lifecycle
  actions and retain only safe filter metadata.
- PaginationLimitResolver defines the API contract as default 15, minimum 1,
  maximum 50, malformed input falling back to 15, with 100 reserved for the
  documented administrative dues surface.
- Sensitive paginated API responses use explicit Resources or DTO-style
  allowlists. Member invoices and cooperative payments no longer return raw
  Eloquent paginator/model serialization.
- Project finance transaction limits and recent notification limits use the
  same resolver with endpoint-specific defaults and ceilings. Static checks
  only prohibit raw request-derived pagination; runtime tests exercise the
  routes.
- Member/access revocation deletes tokens and writes the authoritative audit
  event in one database transaction. Audit failure is monitored through the
  application log and is never retried through the same audit sink.
- Sensitive export files follow requested/completed/failed lifecycle events,
  include a safe checksum after creation, and are deleted when mandatory
  completion audit or response construction fails. The historical export event
  remains best effort after authoritative completion.
- OpenAPI response schemas for members, loans, invoices, cooperative payments,
  and payment batches are generated from explicit resource contracts rather
  than the generic success envelope.

### Consequences

- Background and domain audit records no longer depend on whichever user
  happens to be present in the current request context.
- Audit consumers must handle lifecycle events as intent and outcome events;
  the historical successful member export event remains as a compatibility
  alias.
- New list endpoints must use the shared resolver and explicit response
  contract tests.
- Full-suite and external database verification remain independent review
  responsibilities for this branch.

## 📚 References

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

## 🎯 ADR-028: Member Store Credit Ledger — Signed BIGINT Balance & FIFO Debt-Age

**Status:** ✅ Accepted
**Date:** July 19, 2026
**Deciders:** Development Team

### Context

The cooperative needs an account balance members can use to pay for POS
purchases, including authorized staff (delegate) purchases. The system must
guarantee money correctness under concurrency, prevent overspend beyond a per
member credit limit, and keep an immutable audit trail.

### Decision

1. Represent money as a **signed BIGINT whole-Rupiah** balance on
   `member_store_accounts`, even though the rest of the cooperative/POS domain
   uses `decimal(15,2)`. Whole-Rupiah avoids float entirely and matches the
   product requirement; conversion happens only at the POS boundary.
2. Make the ledger the source of truth. A single service
   (`StoreCreditLedgerService`) mutates the cached balance inside a DB
   transaction with `lockForUpdate()`, writes an immutable entry, then asserts
   the cached balance equals the signed ledger sum.
3. Integrate as a new payment method (`MEMBER_STORE_ACCOUNT`) on the existing
   POS checkout — no parallel POS domain.
4. Enforce idempotency with DB unique constraints
   (`(account_id, idempotency_key)` and `(reference_type, reference_id,
   entry_type)`), plus the HTTP `Idempotency-Key` middleware.
5. Compute debt age with **FIFO allocation** (credits repay oldest purchase
   lots first) — an exact, traceable algorithm rather than an approximation.

### Consequences

- Money correctness is enforced at the model and service layers; PostgreSQL
  row-level locking prevents concurrent overspend (proven by a true
  multi-process concurrency test in the PostgreSQL Concurrency CI job).
- The ledger is append-only; corrections require new reversal/adjustment
  entries.
- Feature is additive; rollback drops four new tables and one payment option
  without affecting historical POS data.

---

## 🎯 ADR-029: Store Account Checkout Attribution Without Member Credentials

**Status:** READY FOR SENIOR REVIEW
**Date:** July 20, 2026
**Deciders:** Engineering

### Decision

- Member Store Account checkout is performed by an authorized cooperative
  cashier and never asks for a member password, delegate PIN, or checkout
  credential.
- `purchaser_name` is required for Store Account purchases. An optional
  `purchase_note` and registered delegate reference may be recorded.
- Ledger purchase and refund entries keep immutable purchaser and note
  snapshots, the cashier actor, transaction number, and timezone-aware
  occurrence time.
- Member summary and ledger views are owner-scoped by the authenticated active
  member and organization. The member portal is view-only.
- Public API `reference_type` values are stable identifiers, not PHP model
  class names.

### Consequences

- Cashiers receive only `cashier_store_credit`; account, limit, funding, and
  adjustment management remains with the existing administrative abilities.
- POS confirmation and failure handling remain atomic, including stock, POS,
  payment, balance, and ledger state.
- Android and web member clients can show who made a purchase and which
  cashier recorded it without exposing internal models or credentials.

---

## 🎯 ADR-030: Ubuntu Canonical Visual Baselines for UI Audit

**Status:** READY FOR SENIOR REVIEW
**Date:** July 23, 2026
**Deciders:** Engineering

### Decision

- Desktop screenshot baselines for the Playwright UI audit are canonical only
  when captured on the GitHub Actions Ubuntu 24.04 environment with the locked
  PHP, Node, Playwright, Chromium, locale, timezone, fixed clock, committed
  audit fonts, and isolated database.
- Host-local screenshots are advisory evidence for functional verification and
  must not replace the Linux baseline because text rasterization varies by OS.
- A baseline candidate must be reviewed for runtime health and page state, and
  two clean Ubuntu captures must have identical screenshot hashes before it is
  committed.
- CI compares against committed baselines and never updates them
  automatically.

### Consequences

- A visual mismatch caused only by a noncanonical local renderer cannot be
  resolved by increasing tolerance or masking text globally.
- Baseline changes remain visible in the pull request and require human review.
- The PR gate remains responsible for proving that the exact checked-out head
  compares successfully against the reviewed Linux baselines.

---

## 🎯 ADR-031: Admin Koperasi Phase 1 Operational Workspace

**Status:** READY FOR SENIOR REVIEW
**Date:** July 28, 2026
**Deciders:** Engineering

### Decision

- Admin Koperasi receives a role-specific, permission-filtered workspace for
  daily member, payment, and dues administration.
- Dashboard data uses organization-scoped, read-only queries and omits
  platform-wide ERP/POS/SHU aggregates from the Admin payload.
- Dues generation remains an explicit POST workflow; the dues index and
  dashboard remain read-only.
- Existing role experiences and domain workflows remain unchanged outside the
  touched Admin operational surfaces.

### Consequences

- Admin users see actionable work queues without links to unauthorized pages.
- Legacy tests and fixtures must provide an organization for non-global
  cooperative operators.
- Administrative activity history remains deferred until a lightweight,
  permission-safe read model is available.

*Last Updated: July 19, 2026*

## 🎯 ADR-032: Host-Isolated QA Sessions and Trusted Proxy Scheme

**Status:** READY FOR SENIOR REVIEW
**Date:** August 25, 2026
**Deciders:** Engineering

### Decision

- The web application trusts only the proxy addresses supplied through the
  `TRUSTED_PROXIES` configuration value; it does not trust arbitrary forwarded
  headers from the internet.
- QA uses an HTTPS application URL and a host-only, QA-specific database
  session cookie. A production deployment must use a different cookie name.
- The proxy chain must preserve the public host and `X-Forwarded-Proto` value
  through Nginx to PHP so Laravel can generate HTTPS URLs and redirects.

### Consequences

- The QA operator must verify the proxy address seen by PHP and configure it in
  the deployment environment before relying on forwarded HTTPS headers.
- Cloudflare and Nginx must keep authenticated and Inertia responses private
  and uncached.
- A missing or incorrect proxy environment value is detectable through the
  trusted-proxy regression test and header-only QA checks.

---

## 🎯 ADR-033: Database Seeding Architecture and Migration Safety Hardening

**Status:** ✅ Accepted
**Date:** August 28, 2026
**Deciders:** Engineering Team

### Context

Accidental execution of `php artisan db:seed` or destructive migration commands (`migrate:fresh`, `migrate:refresh`, `db:wipe`) in staging or production environments creates severe risks:
1. Production/staging financial ledger mutation, dues force-deletion, and dummy member generation.
2. Privileged admin user provisioning with predictable default passwords (`'password'`).
3. Accidental table drop or schema wipe during deployments.

### Decision

1. **Separation of Reference and Demo Seeders:**
   - Default `DatabaseSeeder` in production/staging contains ONLY deterministic, idempotent reference seeders (`TaxRuleSeeder`, `RolePermissionSeeder`, `LoanTypeSeeder`, `JobGradeSeeder`, `LeaveTypeSeeder`, `SalaryComponentTypeSeeder`, `WorkShiftSeeder`, `CooperativeReferenceSeeder`).
   - Demo fixture seeders (`CooperativeSeeder`, `AnggotaSeeder`, `DemoDataSeeder`, `InvoiceSeeder`, `CooperativeManagerRoleSeeder`) are isolated to `local` environments in `DatabaseSeeder`.
2. **Fail-Closed Environment Guards:**
   - All demo and test seeders implement explicit hard environment whitelist checks (`['local', 'testing', 'playwright']`) and throw a `\LogicException` if invoked in staging or production.
3. **Privileged User Bootstrap Separation:**
   - Removed all default-password admin user creation from `RolePermissionSeeder`.
   - Production and staging privileged administrator bootstrapping must use the explicit, secure `php artisan admin:create` command.
4. **Destructive Reset Removal & Collision Safety:**
   - Removed destructive ledger/dues wipe methods (`resetDemoSavingsForMember`).
   - Namespaced demo member identities (`DEMO-ANG-001`, `DEMO-KOP-001`) to prevent collision with real cooperative members.
5. **Laravel Native Prohibit Destructive Commands:**
   - Configured `DB::prohibitDestructiveCommands(! app()->environment('local', 'testing', 'playwright'))` in `AppServiceProvider`.

### Deployment Contract

| Environment | `migrate --force` | `db:seed` (Default) | `migrate:fresh` / `migrate:refresh` / `db:wipe` | Demo Seeders (`--class=...`) | Admin Bootstrap |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Production** | ✅ Allowed (Standard Deploy) | ⚠️ Not normal deploy; Safe reference-only | 🚫 FORBIDDEN (Prohibited) | 🚫 FORBIDDEN (Throws LogicException) | Explicit `php artisan admin:create` |
| **Staging / QA** | ✅ Allowed (Standard Deploy) | ⚠️ Not normal deploy; Safe reference-only | 🚫 FORBIDDEN (Prohibited) | 🚫 FORBIDDEN (Throws LogicException) | Explicit `php artisan admin:create` |
| **Local / Test** | ✅ Allowed | ✅ Allowed (Seeds reference + demo) | ✅ Allowed | ✅ Allowed | Pre-seeded or `admin:create` |

