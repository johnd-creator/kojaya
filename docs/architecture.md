# KojayaPro & Kojayaku - System Architecture

## 🏗️ Architecture Overview

KojayaPro dan Kojayaku adalah **dual-platform system** terintegrasi:
- **KojayaPro**: Sistem admin ERP untuk pengelolaan operasional koperasi
- **Kojayaku**: Aplikasi member-facing untuk anggota cek simpanan, pinjaman, poin, dan transaksi

Kedua sistem berbagi satu database dan terintegrasi via API.

---

## 🎯 Architecture Pattern

### **Pattern: Service-Oriented Monolith**

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend Layer                            │
│  Vue 3 + TypeScript + Inertia.js + Tailwind CSS v4          │
│  - Server-side rendering (SSR) supported                    │
│  - Client-side navigation (SPA-like)                        │
└─────────────────────────────────────────────────────────────┘
                              ↕ HTTP/WebSocket
┌─────────────────────────────────────────────────────────────┐
│                     Backend Layer                             │
│                    Laravel 12 + PHP 8.2+                     │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Presentation Layer                                     │  │
│  │ - Controllers (Actions + Traditional)                 │  │
│  │ - Form Requests (Validation)                          │  │
│  │ - Inertia Middleware                                  │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Business Logic Layer (Services)                        │  │
│  │ - PayrollCalculatorService                            │  │
│  │ - Pph21TerService, BpjsCalculationService             │  │
│  │ - NotificationService, AuditLogService                │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Data Access Layer                                     │  │
  │  │ - Eloquent Models (114 models, 52 with UUID)              │  │
│  │ - Query Scopes, Relationships                         │  │
│  │ - API Resources (JSON transformation)                 │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↕ SQL (Eloquent)
┌─────────────────────────────────────────────────────────────┐
│                     Data Layer                                │
│                    PostgreSQL 15+                             │
│  - Database: kojaya_erp                                     │
│  - Multi-organization tenancy                             │
│  - Audit logs, soft deletes                               │
│  - Indexes for performance                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Technology Stack

### **Backend Stack**

#### **Core Framework**
- **Laravel 12** - Latest LTS (as of 2026)
- **PHP 8.2+** - Modern PHP features (constructor promotion, readonly properties, enums)
- **PostgreSQL** - Primary database (production-grade)

#### **Key Laravel Packages**

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/fortify` | v1.30 | Authentication scaffolding |
| `laravel/sanctum` | v4.0 | API token authentication |
| `laravel/wayfinder` | v0.1.9 | Auto-discovery routes & controllers |
| `spatie/laravel-permission` | v7.2 | Role-based access control |
| `inertiajs/inertia-laravel` | v2.0 | Server-side routing for Vue |
| `maatwebsite/excel` | v3.1 | Excel import/export |
| `barryvdh/laravel-dompdf` | v3.1 | PDF generation |
| `laravel/pint` | v1.24 | Code formatting |
| `laravel/pail` | v1.2 | Real-time log viewing |

#### **Indonesian Compliance Services**
- **BPJS** - Social security calculation (`BpjsCalculationService`)
- **PPh21** - Income tax calculation (`Pph21TerService`)
- **eFaktur** - Tax invoicing (`DjpEfakturApiService`)
- **Bank Formats** - Indonesian bank export formats (`BankExportService`)

---

### **Frontend Stack**

#### **Core Framework**
- **Vue 3.5** - Composition API, `<script setup>`, TypeScript
- **Inertia.js 2.3** - SPA-like experience without API complexity
- **TypeScript 5.2** - Full type safety
- **Vite 7.0** - Lightning-fast build tool & HMR

#### **UI Libraries**
- **Tailwind CSS 4.1** - Utility-first CSS (latest version)
- **Reka UI 2.6** - Unstyled component library (Headless UI)
- **shadcn-vue** - Pre-built UI components (New York v4 style)
- **Lucide Vue Next** - Icon library
- **Chart.js 4.5** - Data visualization
- **dhtmlx-gantt 9.1** - Gantt chart for projects

#### **Build Tools**
- **Vite Plugin Wayfinder** - TypeScript route generation
- **ESLint 9.17** - Linting with TypeScript support
- **Prettier 3.4** - Code formatting
- **Vue TSC 2.2** - TypeScript type checking

---

### **Development Tools**

#### **Code Quality**
- **Laravel Pint** - PHP code formatter (Laravel preset)
- **ESLint** - JavaScript/TypeScript linter
- **Prettier** - Universal code formatter
- **PHPUnit 11.5** - Testing framework (not Pest)

#### **Infrastructure**
- **Docker** - Containerization (PHP 8.4 FPM, Node.js 22)
- **Composer** - PHP dependency management
- **NPM** - Frontend dependency management

---

## 📁 Directory Structure

```
kojaya/
├── app/
│   ├── Actions/                    # Single-action controllers (Wayfinder)
│   │   └── Fortify/                # Laravel Fortify actions
│   ├── Console/                    # Artisan commands
│   │   └── Commands/               # Custom commands
│   ├── Enums/                      # PHP 8 enums for type-safe constants
│   │   ├── CertificateStatus.php
│   │   ├── CertificateType.php
│   │   └── ...
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/               # API controllers (technician, cooperative)
│   │   │   │   ├── V1/            # API v1 endpoints
│   │   │   │   └── TechnicianWorkOrderController.php
│   │   │   └── [Module]Controller.php
│   │   ├── Middleware/            # Custom middleware
│   │   ├── Requests/              # Form Request validation
│   │   └── Resources/             # API Resources (JSON transformation)
│   ├── Jobs/                      # Queued jobs
│   ├── Listeners/                 # Event listeners
  │   ├── Models/                    # Eloquent models (114 models: 52 UUID, 62 auto-increment/string IDs)
│   ├── Observers/                 # Model observers
│   ├── Providers/                 # Service providers
│   ├── Services/                  # Business logic layer
│   │   ├── PayrollCalculatorService.php
│   │   ├── Pph21TerService.php
│   │   ├── BpjsCalculationService.php
│   │   ├── NotificationService.php
│   │   └── ...
│   └── Imports/                   # Excel import classes
├── bootstrap/
│   └── app.php                    # Laravel 12 middleware configuration
├── config/                        # Configuration files
├── database/
│   ├── factories/                 # Model factories for testing
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
├── public/                        # Public web root
├── resources/
│   ├── css/                       # Tailwind CSS entry point
│   └── js/
│       ├── actions/               # Wayfinder-generated controller imports
│       ├── api/                   # API client utilities
│       ├── components/            # Vue components
│       │   └── ui/                # shadcn-vue components (auto-generated)
│       ├── composables/           # Vue composables (useAppearance, etc.)
│       ├── layouts/               # Inertia layout components
│       ├── pages/                 # Inertia page components
│       │   ├── Attendance/
│       │   ├── Budget/
│       │   ├── Dashboard.vue
│       │   └── ...
│       ├── routes/                # Wayfinder-generated route functions
│       ├── types/                 # TypeScript type definitions
│       ├── app.ts                 # Vue app entry point
│       └── ssr.ts                 # SSR entry point
├── routes/
│   ├── api.php                    # API routes (v1, technician, etc.)
│   ├── console.php                # Artisan command routes
│   └── web.php                    # Web routes (Inertia pages)
├── tests/
│   ├── Feature/                   # Feature tests
│   └── Unit/                      # Unit tests
├── docs/                          # Project documentation
├── .env.example                   # Environment variables template
├── .gitignore                     # Git ignore rules
├── CLAUDE.md                      # Claude Code AI instructions
├── AGENTS.md                      # Laravel Boost guidelines
├── composer.json                  # PHP dependencies
├── package.json                   # Node.js dependencies
├── phpunit.xml                    # PHPUnit configuration
├── pint.json                      # Pint configuration
├── tsconfig.json                  # TypeScript configuration
└── vite.config.ts                 # Vite configuration
```

---

## 🔐 Security Architecture

### **Authentication Layers**

#### **1. Web Session Authentication** (Admin Panel)
- **Mechanism:** Laravel Fortify + Session-based auth
- **Middleware:** `auth:web`
- **Features:**
  - Email verification
  - Two-factor authentication (2FA)
  - Password confirmation
  - Session management

#### **2. Token Authentication** (Mobile/API)
- **Mechanism:** Laravel Sanctum (token-based)
- **Middleware:** `auth:sanctum`
- **Features:**
   - API tokens with 30-day expiration (configurable)
   - Token abilities are app-specific and granular: member (`profile:read`, `member:*`), ESS (`ess:*`, `attendance:*`, `payroll:read`), technician (`work-orders:*`), and admin cooperative/POS/report abilities resolved from current permissions.
   - New tokens never receive `*`; `cooperative:read/write` are legacy compatibility abilities governed by the validated cutover phase and grace deadlines.
   - Mobile-first authentication

#### **3. Role-Based Access Control (RBAC)**
- **Package:** Spatie Laravel Permission
- **Roles:**
  - System Admin
  - Pengurus Koperasi
  - Manajer Koperasi
  - Admin Koperasi
  - Kasir Koperasi
  - HR Manager
  - Finance Staff
  - Project Manager
  - Technician
  - Employee
  - Cooperative Member

### Cooperative Role Hierarchy

Within cooperative operations, `Pengurus Koperasi` is the highest cooperative authority, `Manajer Koperasi` sits below Pengurus for operational review, and `Admin Koperasi` handles day-to-day administration. Loan approval follows this hierarchy: manager review first, final Pengurus approval second; Admin Koperasi is not a loan approver.

### **Authorization Patterns**

```php
// Controller-based authorization
abort_unless($user->hasRole(['System Admin', 'Pengurus']), 403);

// Policy-based authorization (recommended for future)
$this->authorize('update', $project);

// Middleware-based authorization
Route::middleware(['can:manage-payroll'])->group(function () {
    // Payroll routes
});
```

### **Security Features**

| Feature | Implementation |
|---------|---------------|
| CSRF Protection | Laravel `@csrf` token for web forms |
| XSS Prevention | Inertia.js automatic escaping + Laravel escaping |
| SQL Injection | Eloquent ORM with parameterized queries |
| Password Hashing | Laravel `Hash::make()` with bcrypt |
| File Upload Validation | MIME type validation, max size limits |
| Rate Limiting | Laravel Fortify (5 failed attempts = 429) |
| Audit Logging | `AuditLogService` for sensitive operations |
| Encryption | `config/app.key` for data encryption |

---

## 🗄️ Database Design

### **Schema Principles**

1. **UUID Primary Keys** - 36 tables use UUID primary keys (via `HasUuids` trait), 46 use auto-increment integers
2. **Soft Deletes** - `deleted_at` column for soft deletion
3. **Audit Trail** - `created_at`, `updated_at` timestamps
4. **Organization Scope** - Multi-tenancy via `organization_id`
5. **Polymorphic Relations** - Flexible relationships (approvals, audit logs)

### **Key Relationships**

```
Organization (1) ──< (N) Employees
Employee (1) ────< (N) Attendance
Employee (1) ────< (N) LeaveRequests
Employee (1) ────< (N) Certificates
Employee (1) ────< (N) MedicalCheckups

Project (1) ────< (N) Tasks
Project (1) ────< (N) Teams
Project (1) ────< (N) Milestones

WorkOrder (1) ──< (N) Checklists
WorkOrder (1) ──< (N) SpareParts

CooperativeMember (1) ──< (N) DuesInvoices
CooperativeMember (1) ──< (N) Payments

PosProduct (1) ──< (N) PosTransactionItems
PosTransaction (1) ──< (N) Items
```

### **Indexing Strategy**

- **Composite Indexes** - Frequently queried columns (e.g., `organization_id`, `status`)
- **Foreign Key Indexes** - All foreign keys indexed
- **Unique Indexes** - Prevent duplicate data (e.g., `employee_code`)

---

## 🔄 Data Flow Architecture

### **Request Lifecycle (Web)**

```
User Request (Browser)
       ↓
[Nginx/Apache]
       ↓
[Laravel 12 Router]
       ↓
[Middleware Pipeline]
  → Session Authentication
  → CSRF Verification
  → Activity Logging
       ↓
[Controller (Action)]
       ↓
[Service Layer] (Business Logic)
       ↓
[Eloquent Model] (Data Access)
       ↓
[PostgreSQL Database]
       ↓
[Response]
  → Inertia::render()
       ↓
[Vue 3 Component]
       ↓
[Server-Side Rendered HTML]
       ↓
[Client-Side Hydration]
       ↓
[Interactive UI]
```

### **Request Lifecycle (API)**

```
Mobile App Request
       ↓
[API Route]
       ↓
[Sanctum Authentication]
       ↓
[API Controller]
       ↓
[Service Layer]
       ↓
[Eloquent/API Resource]
       ↓
[JSON Response]
```

---

## ⚡ Performance Optimizations

### **Backend Optimizations**

1. **Eager Loading** - Prevent N+1 queries
   ```php
   Project::with(['tasks', 'team', 'milestones'])->get();
   ```

2. **Query Scopes** - Reusable query fragments
   ```php
   Project::active()->authorized()->latest()->paginate(15);
   ```

3. **Pagination** - Large datasets
   ```php
   // Cursor pagination for infinite scroll
   Project::cursorPaginate(50);
   ```

4. **Database Indexing** - Fast lookups
5. **Queue Jobs** - Background processing (notifications, reports)
6. **Cache Strategy** - Redis for session & cache (optional)

### **Frontend Optimizations**

1. **Code Splitting** - Vite automatic chunking
2. **Lazy Loading** - Components loaded on-demand
3. **SSR** - Server-side rendering for fast initial load
4. **Asset Optimization** - Minified CSS/JS in production
5. **Image Optimization** - Lazy loading, responsive images

### **Build Performance**

| Environment | Build Time | Assets Size |
|-------------|------------|-------------|
| Development | ~2s (HMR) | N/A |
| Production  | ~30s | ~1.5 MB (gzipped) |

---

## 📱 Mobile-Ready Architecture

### **Dual-Platform Integration**

KojayaPro dan Kojayaku terintegrasi melalui shared database dan RESTful API:

```
┌─────────────────────────────────────────────────────┐
│              KojayaPro (Web Admin)                  │
│  ┌─────────────────────────────────────────────────┐  │
│  │  ERP • POS • Inventori • Akuntansi                │  │
│  │  • Simpan Pinjam • Approval • Laporan              │  │
│  └─────────────────────────────────────────────────┘  │
│                      ↕ API                           │
│              PostgreSQL Database                     │
└─────────────────────────────────────────────────────┘
                      ↕
┌─────────────────────────────────────────────────────┐
│              Kojayaku (Member App)                   │
│  ┌─────────────────────────────────────────────────┐  │
│  │  Simpanan • Pinjaman • Poin • Transaksi          │  │
│  │  • Profil • Notifikasi                            │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### **API-First Design**

Backend didesain dari awal untuk mendukung mobile apps:

1. **RESTful API** - Standard HTTP methods (GET, POST, PUT, DELETE)
2. **JSON Responses** - Consistent response format
3. **Token Auth** - Sanctum tokens with 30-day expiration and scoped abilities
4. **API Versioning** - `/api/v1/` prefix
5. **Rate Limiting** - 3-tier throttle (60/min read, 30/min write, 5/min login)
6. **Documentation** - See `docs/flutter/` for complete Flutter development docs

### **API Endpoints (Current)**

| Group | Prefix | Endpoints | Auth |
|-------|--------|-----------|------|
| User Profile | `/api/user` | GET current user | sanctum + profile:read |
| Cooperative Members | `/api/v1/members` | CRUD + activate/resign | sanctum + cooperative:* |
| Cooperative Dues | `/api/v1/dues` | List invoices, generate | sanctum + cooperative:* |
| Cooperative Payments | `/api/v1/dues/payments` | Record, approve | sanctum + cooperative:* |
| POS Products | `/api/v1/pos/products` | List | sanctum + pos:read |
| POS Transactions | `/api/v1/pos/transactions` | Create | sanctum + pos:write |
| Cooperative Reports | `/api/v1/reports` | Summary, sales | sanctum + reports:read |
| Technician Work Orders | `/api/technician/work-orders` | List, start, complete, checklists | sanctum + work-orders:* |
| Employee Certificates | `/api/employees/{id}/certificates` | CRUD + upload | sanctum + employee-documents:* |
| Medical Checkups | `/api/employees/{id}/mcu` | CRUD + upload | sanctum + employee-documents:* |
| Compliance Reports | `/api/reports` | Certificate/MCU compliance | sanctum + reports:read |

### **Mobile App Integration Points**

| Mobile App Type | API Endpoints | Primary Use Cases |
|-----------------|---------------|-------------------|
| **Kojayaku (Cooperative App)** | `/api/v1/members`, `/api/v1/dues/*`, `/api/v1/pos/*`, `/api/v1/reports/*` | Manajemen anggota, iuran, pembayaran, POS, laporan koperasi |
| **Technician App** | `/api/technician/work-orders/*` | Work orders, checklists, spare parts |
| **Compliance App** | `/api/employees/*/certificates`, `/api/employees/*/mcu`, `/api/reports/*-compliance` | Sertifikasi karyawan, medical checkup, kepatuhan |

---

## 🔧 Deployment Architecture

### **Development Environment**

```
┌─────────────────────────────────────┐
│     Developer Workstation           │
│  - PHP 8.4 (local or Docker)       │
│  - Node.js 22 (local)               │
│  - PostgreSQL 15 (Docker)           │
│  - Redis (Docker, optional)         │
└─────────────────────────────────────┘
```

### **Production Architecture (Recommended)**

```
                        ┌─────────────────┐
                        │   Load Balancer │
                        │    (Nginx)       │
                        └────────┬────────┘
                                 │
                ┌────────────────┴────────────────┐
                │                                  │
         ┌──────▼──────┐                  ┌──────▼──────┐
         │  App Server  │                  │  App Server  │
         │   (PHP 8.4)  │                  │   (PHP 8.4)  │
         │  - Laravel   │                  │  - Laravel   │
         │  - KojayaPro │                  │  - KojayaPro │
         │  - Wayfinder │                  │  - Wayfinder │
         │  - API       │                  │  - API       │
         └──────┬───────┘                  └──────┬───────┘
                │                                  │
                └────────────────┬────────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   PostgreSQL (Primary)   │
                    │   + PostgreSQL Replica   │
                    └────────────┬────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   Redis (Session/Cache)  │
                    └─────────────────────────┘
                                 │
                ┌────────────────┴────────────────┐
                │                                  │
         ┌──────▼──────┐                  ┌──────▼──────┐
         │  Kojayaku   │                  │  Mobile Apps│
         │  (Web App)  │                  │  (Android/  │
         │             │                  │   iOS)      │
         └─────────────┘                  └─────────────┘
```

### **Infrastructure Requirements**

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4 cores |
| RAM | 4 GB | 8 GB |
| Storage | 40 GB SSD | 80 GB SSD |
| Database | PostgreSQL 15 | PostgreSQL 15+ with replica |
| Web Server | Nginx | Nginx + PHP-FPM |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |

---

## 🧪 Testing Strategy

### **Test Coverage**

- **Unit Tests** - Service layer logic (payroll, tax calculations)
- **Feature Tests** - End-to-end API testing (100+ test methods across 108 test files)
- **Browser Tests** - Inertia page interactions (planned)

### **Testing Tools**

- **PHPUnit 11.5** - Testing framework
- **Faker** - Test data generation
- **Factories** - 53 model factories for test data
- **Database Migrations** - Rollback between tests

---

## 📈 Scalability Considerations

### **Horizontal Scaling**
- Stateless application design
- Database read replicas
- Redis for shared cache
- Load balancer ready

### **Vertical Scaling**
- Optimized database queries
- Queue workers for background jobs
- Efficient caching strategy

### **Performance Targets**
- **Response Time:** < 500ms (95th percentile)
- **Throughput:** 1000+ concurrent users
- **Uptime:** 99.9%

---

## 🔒 Storage & Security Boundaries (SEC-P0-03)

### Filesystem Disk Segmentation
To protect sensitive employee documents and PII from unauthorized web access:
- **`employee_documents` (Private Disk):**
  - Path: `storage/app/private/employee-documents`
  - Visibility: `private`
  - Scope: Employee certificates (`certificates/{employeeId}/...`) and medical checkup records (`mcu/{employeeId}/...`).
  - Access Model: Strictly authenticated through `EmployeeDocumentStorage`, requiring token ability `employee-documents:read`, organization scope resolution via `OrganizationScopeService`, and served via streaming binary responses with security headers (`X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`).
- **`public` (Public Disk):**
  - Path: `storage/app/public` (symlinked to `public/storage`)
  - Visibility: `public`
  - Scope: Non-sensitive public assets only (e.g., product catalog images, public branding).
  - Rule: Sensitive ERP documents, payroll records, employee certificates, and medical records MUST NEVER be stored on or served via the public disk.

### Operational Migration Lifecycle States
The SEC-P0-03 hardening operates under six documented lifecycle states:
1. **Code Deployed; New Uploads Private:** Code deployed; new files stored on `employee_documents`.
2. **Legacy Fallback Active:** `EmployeeDocumentStorage` resolves existing public files during the migration window.
3. **Copy Migration Verified:** `php artisan security:migrate-employee-documents-private --execute` copies legacy files with checksum verification.
4. **Public Cleanup Verified:** `php artisan security:migrate-employee-documents-private --execute --cleanup` removes verified public copies and confirms absence.
5. **Orphan Inventory Resolved:** Unreferenced public orphan files (accounting for soft-deleted records via `withTrashed()`) are inventoried and reconciled.
6. **SEC-P0-03 Operationally Closed:** Zero unresolved legacy files or orphans remain, and migration command completes with success.

---

## 🏢 Multi-Tenant & Organization Isolation Architecture (SEC-P1-01)

### Core Invariant
All multi-tenant data access and operations in Kojaya must strictly enforce the following security invariant:
```text
authenticated identity + functional permission + organization visibility + object / parent ownership = authorized operation
```

### Canonical Foundation Architecture
1. **Single Source of Truth (`OrganizationScopeService`):**
   - The central service `App\Services\Authorization\OrganizationScopeService` defines all registered model paths, model-specific global permissions, query scoping, and visibility assertions.
   - For the cooperative domain, `App\Contracts\OrganizationScopedQueryService` acts as the domain-focused facade.
   - No ad-hoc scoping frameworks or global Eloquent scopes (`static::addGlobalScope`) are permitted.

2. **Canonical Primitives:**
   - **`scopeVisibleTo(Builder $query, User $user, ?string $globalPermission = null): Builder`**
     Applies SQL `WHERE organization_id = ?` (for direct models) or `whereHas('relation', ...)` (for relational models) based on user's organization visibility.
   - **`resolveVisible(Builder|string $queryOrClass, User $user, string|int $id, ?string $globalPermission = null): Model`**
     Scopes query first, then executes `findOrFail($id)`. Throws `ModelNotFoundException` (404 Not Found) if the object belongs to another tenant, avoiding resource existence disclosure.
   - **`assertVisible(User $user, Model $model): void`**
     Enforces tenant visibility on an already-resolved model instance. Throws `AuthorizationException` (403 Forbidden) if outside user visibility.
   - **`assertOrganizationIdentifier(string|int $organizationId): string`**
     Validates organization ID existence in `organizations` table.

3. **Ownership Paths & Contract:**
   - **Direct Ownership:** Model defines `'organization_id'` directly on its table.
   - **Relational Ownership:** Model derives ownership through relations (e.g., `RewardRedemption -> member.organization_id`, `CooperativeMemberSaving -> member.organization_id`).
   - **Model Contract:** Models implement `App\Contracts\OrganizationScopedModel` declaring `organizationScopePath(): string` to specify their canonical ownership path. Global domain permissions remain explicitly defined in the centralized `OrganizationScopeService::GLOBAL_PERMISSIONS` registry. Models implementing this contract take precedence over registry path mappings.
   - **Supported vs. Unresolved Models:** Exactly 38 models are currently registered or contracted in the foundation. Unregistered models (such as POS transaction, product, and category models) are recognized ownership candidates and domain gaps to be remediated in later P1 tasks; they fail closed until explicitly registered.

4. **Rules of Isolation:**
   - **Rule A (Tenant Isolation):** Standard users are strictly isolated to their own organization.
   - **Rule B (Explicit Global Authority):** Global visibility requires explicit domain permission (e.g. `view_cooperative_all`, `view_employee_all`), never role names, `is_admin`, null org, or wildcard tokens.
   - **Rule C (Null Org Fails Closed):** User with `organization_id = null` and no global permission throws `AuthorizationException`.
   - **Rule D (Direct Ownership):** Scoped via qualified `organization_id`.
   - **Rule E (Relational Ownership):** Scoped via `whereHas` with verified relation path.
   - **Rule F (Parent / Child Security):** Resolve parent via `resolveVisible`, then resolve child via parent relationship (`$parent->children()->findOrFail($childId)`).
   - **Rule G (Client Forgery Prevention):** Prohibit client assignment of tenant identifiers (`'organization_id' => ['prohibited']`).
   - **Rule H (Aggregates Are Tenant Data):** All counts, sums, averages, and group-by reports must be scoped to the tenant organization.

---

## 🔮 Future Architecture Improvements

### **Short Term (3-6 months)**
- [x] Add OpenAPI/Swagger documentation (`bin/openapi.sh`)
- [x] Implement rate limiting (3-tier: api 60/min, api-write 30/min, login 5/min)
- [x] Add automated testing coverage (53 factories, 1000+ test methods)
- [x] Strict API Token Ability Boundaries (SEC-P0-02)
- [x] Sensitive Employee Document Private Storage Hardening (SEC-P0-03)
- [ ] Database query optimization
- [ ] Redis caching implementation
- [x] **Kojayaku Architecture** - Flutter app architecture docs completed (see `docs/flutter/`)
- [x] **Kojayaku API** - Cooperative, POS, Technician, Employee Document endpoints live

### **Medium Term (6-12 months)**
- [ ] Microservices for specific modules (POS, Payroll)
- [ ] Event-driven architecture (Laravel Events + Queues)
- [ ] Real-time notifications (WebSocket/SSE)
- [ ] Advanced analytics dashboard
- [ ] **Kojayaku Flutter App** - Native Android/iOS apps (architecture docs ready)
- [x] **Payment Gateway Integration** - Midtrans sandbox with fail-closed security (SEC-P0-01)
- [ ] **Push Notifications** - Firebase/WhatsApp untuk member notifications
- [ ] **ESS API** - Attendance clock-in/out, leave requests, overtime, payslips untuk mobile

### **Long Term (12+ months)**
- [ ] GraphQL API (alternative to REST)
- [ ] Elasticsearch for search
- [ ] Multi-region deployment
- [ ] AI-powered insights
- [ ] **Advanced Kojayaku Features** - Machine learning untuk credit scoring, fraud detection

---

*Last Updated: September 1, 2026*

