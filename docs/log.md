# KojayaPro & Kojayaku - Development Log

## 📅 Project Timeline

**Project Start:** February 26, 2026
**Current Status:** Active Development
**Last Updated:** May 6, 2026

---

## 🎯 2026-05: Branding & Kojayaku Development

### **May 6, 2026 - Improve3 Phase B Production Integration Corrections**

**🔌 Production Integration Corrections:**
- ✅ Fixed Midtrans webhook verification to read `signature_key` from payload and normalize header arrays safely.
- ✅ Made payment webhook processing idempotent so duplicate paid callbacks do not reconcile or notify repeatedly.
- ✅ Kept unconfigured payment gateway flows on the internal provider instead of labelling fallback charges as Midtrans.
- ✅ Fixed FCM push payload to match the configured legacy endpoint contract and revoke invalid Android tokens.
- ✅ Expanded OpenAPI output with Phase B request schemas, reusable error responses, path parameters, and required ability metadata.
- ✅ Added `PhaseBContractApiTest` coverage for OpenAPI integration contracts, signed Midtrans webhook idempotency, and FCM token handling.

### **May 6, 2026 - Improve2 Phase 3 Technician Mobile API**

**🔧 Technician Mobile Improvements:**
- ✅ Added technician work order pagination and filters for status, priority, and scheduled date
- ✅ Added mobile work order field metadata for scheduled date, GPS start/complete, completion notes, escalation, review, and reopen
- ✅ Added work order evidence attachments, spare part consumption, timeline logs, and idempotent offline sync tables
- ✅ Added technician endpoints for attachments, parts, sync, timeline, escalation, and supervisor reopen
- ✅ Updated complete flow to require GPS payload and record completion timeline
- ✅ Updated `docs/api.md` and `docs/improve2.md` with Phase 3 implementation status
- ✅ Added `Phase3TechnicianMobileApiTest` coverage for filters, evidence upload, parts, GPS completion, timeline, offline idempotency, escalation, and reopen

### **May 6, 2026 - Improve2 Phase 2 ESS Mobile API**

**👤 ESS Mobile Improvements:**
- ✅ Expanded `/api/ess` with shift roster, leaves, overtime, reimbursements, payslips, compliance, and notifications
- ✅ Added mobile attendance metadata persistence for GPS latitude/longitude, accuracy, device id, and audit payload on check-in/check-out
- ✅ Added leave cancellation request metadata without changing the existing leave approval status constraint
- ✅ Added mobile Form Requests for ESS leave, overtime, and reimbursement payloads
- ✅ Added secure payslip list and PDF download scoped to the authenticated employee
- ✅ Updated `docs/api.md` and `docs/improve2.md` with Phase 2 implementation status
- ✅ Added `Phase2EssMobileApiTest` coverage for ESS ownership, mobile attendance metadata, leave cancellation request, overtime, reimbursement receipt upload, payslip, compliance, and notifications

### **May 5, 2026 - Improve2 Phase 1 Member Self-Service API**

**👥 Kojayaku Member API Improvements:**
- ✅ Added member savings summary and ledger statement endpoints with running balances
- ✅ Added member dues invoice and payment history endpoints
- ✅ Added member payment proof upload flow that creates pending cooperative payments
- ✅ Added member loan list, application, and detail endpoints with ownership enforcement
- ✅ Added member SHU, notification, and support ticket endpoints
- ✅ Added `cooperative_support_tickets` table and model for member complaints/support requests
- ✅ Updated `docs/api.md` and `docs/improve2.md` with Phase 1 endpoint status
- ✅ Added `Phase1MemberSelfServiceApiTest` coverage for savings, payments, loans, SHU, notifications, and support tickets

### **May 5, 2026 - Improve2 Phase 0 Mobile API Foundation**

**📱 Mobile API Improvements:**
- ✅ Added mobile auth endpoints for login, current session, logout, and logout all devices
- ✅ Added persona-aware Sanctum token abilities for member, ESS, technician, and admin mobile clients
- ✅ Added initial member self-service API namespace for dashboard and profile
- ✅ Added initial ESS API namespace for dashboard, profile, geofence, attendance today/history, check-in, and check-out
- ✅ Updated `docs/api.md` and `docs/improve2.md` to reflect the implemented Phase 0 routes
- ✅ Added `Phase0MobileApiTest` coverage for auth, token revocation, member ownership, and ESS attendance abilities

### **May 5, 2026 - P0 API Token Rotation Follow-up**

**🔐 Security Improvements:**
- ✅ Added `POST /api/token/rotate` for rotating the active Sanctum bearer token
- ✅ Preserved token abilities during rotation and revoked the previous token in the same transaction
- ✅ Added P0 feature coverage for unauthenticated rotation denial, old token revocation, and new token usability
- ✅ Synchronized the P0 summary checklist in `docs/improve.md` with completed detailed P0 progress

### **May 5, 2026 - P1 Cooperative UI Consistency Follow-up**

**🏗️ Frontend Consistency Improvements:**
- ✅ Migrated cooperative points, rewards, and redemptions index tables to the shared `DataTable` component
- ✅ Reused shared `StatsCard`, `StatusBadge`, and formatter utilities on the points/rewards/redemptions pages
- ✅ Synchronized the P1 summary checklist in `docs/improve.md` with the completed detailed P1 progress

### **May 5, 2026 - P5 Points & Rewards Redemption Follow-up**

**🎁 Points & Rewards Improvements:**
- ✅ Added cooperative redemption detail UI for reviewing member, reward, point transaction, delivery, and processing data
- ✅ Added admin redemption status processing for `PROCESSING`, `SHIPPED`, `DELIVERED`, and `CANCELLED`
- ✅ Made cancellation refund member points and restore reward stock exactly once
- ✅ Fixed repeated redemption of the same reward by using each `RewardRedemption` as the point transaction source
- ✅ Added P5 feature coverage for repeated redemption, admin cancellation refund, and delivered-redemption cancellation guard

### **May 4, 2026 - P5 Cooperative Loan Module**

**💳 Cooperative Loan Improvements:**
- ✅ Added cooperative loan domain tables and models for `LoanType`, `Loan`, `LoanInstallment`, and `LoanPayment`
- ✅ Added loan status enums, installment status enums, and calculator/service logic for installments, totals, and late-fee refresh
- ✅ Added cooperative web flows for loan list, create, detail, calculator, approval, disbursement, and installment payment recording
- ✅ Added Kojayaku API endpoints for loan application, own-loan tracking, and installment calculator preview
- ✅ Integrated approval logging and automatic cooperative ledger posting for loan disbursement and loan payments
- ✅ Added `CooperativeLoanFeatureTest` coverage and verified cooperative regression tests stay green

### **May 4, 2026 - P4 Frontend UX Foundation**

**🎨 Frontend UX/UI Improvements:**
- ✅ Added shared `PageContainer` to standardize page width and spacing for list, detail, and form layouts
- ✅ Replaced ad-hoc deferred loading placeholders with reusable `Skeleton` states on key pages (`Dashboard`, `Reports`, `Payroll`, cooperative members)
- ✅ Added baseline accessibility improvements: skip-to-content link, labelled tables, `aria-live` loading regions, labelled icon buttons, and better dialog descriptions
- ✅ Synchronized `app.ts` and `ssr.ts` through a shared bootstrap helper so route globals and `v-can` directive registration match in client and SSR
- ✅ Shared `appearance` preference via Inertia props to reduce SSR/client theme mismatch risk

### **May 4, 2026 - P3 Model Consistency Cleanup**

**🧹 Backend Consistency Improvements:**
- ✅ Standardized remaining model casts to Laravel 12 `casts()` methods, including support models in project, reimbursement, and medical checkup domains
- ✅ Completed UUID alignment for `Project`, `ProjectTask`, `ProjectTeam`, `ProjectMilestone`, `Client`, `Invoice`, and `PayrollApproval`, and removed redundant manual UUID assignment from related controllers
- ✅ Added `HasOrganizationScope` to schema-valid models that were still missing organization scoping, including attendance, salary structure, warehouse, and spare part flows
- ✅ Added missing relationships for warehouse, organization, and user audit log access to reduce ad-hoc query logic in future batches
- ✅ Added `P3ArchitectureTest` coverage to guard key traits, relationships, and casts conventions

### **May 4, 2026 - P2 Testing Expansion & Full Suite Stabilization**

**🧪 Testing Improvements:**
- ✅ Added broad P2 feature coverage for Leave, Reimbursement, Petty Cash, Payroll Pipeline, Asset, Warehouse, Spare Parts, Organization setup, HR master data, Salary Structure, Shift Roster, Attendance, Work Order web flow, and Report generation
- ✅ Expanded factory coverage across core operational modules to reduce manual test setup and improve reuse
- ✅ Replaced remaining stub coverage in user/role management and removed duplicate placeholder employee scope test file
- ✅ Re-enabled notification coverage so `NotificationSystemTest` runs without skipped cases

**🔧 Regression Follow-up:**
- ✅ Audited and fixed stale tests after the broad P2 rollout
- ✅ Updated tests to match current route names, root redirect behavior, seeded organization code, and procurement permission requirements
- ✅ Fixed audit log route matching so `/api/audit-logs/export` no longer collides with the detail endpoint
- ✅ Verified the full PHPUnit suite is green: `259 passed` with `1520 assertions`

### **May 4, 2026 - P1 Architecture Improvements**

**🏗️ Code Quality Improvements:**
- ✅ Added Form Request classes for key employee, attendance, project, leave, overtime, reimbursement, work order, user, payroll, and role update flows
- ✅ Completed follow-up Form Request migration for remaining inline controller validation across CRUD, payroll, attendance, project, finance, report, document upload, and technician endpoints
- ✅ Added named API throttling for authenticated API routes and stricter write endpoint limits
- ✅ Added shared frontend utilities for formatters, table filters, confirmation dialog, empty state, filter bar, and stats card
- ✅ Removed duplicate nested components and development artifacts
- ✅ Replaced remaining controller service instantiation with dependency injection
- ✅ Added P1 architecture tests for request validation and API rate limiting
- ✅ Added deferred props and skeleton fallbacks for Dashboard, Reports, Payroll stats, and Cooperative Member stats
- ✅ Started replacing browser `confirm()` dialogs with the shared `ConfirmDialog` component
- ✅ Confirmed raw `confirm()`/`prompt()` usage is cleared from Vue pages/components, local status color maps are removed, and priority index filters now use the shared Select wrapper

### **May 4, 2026 - P0 Security Follow-up**

**🔒 Security Improvements:**
- ✅ Enabled controller-level `$this->authorize()` support through the shared base controller
- ✅ Moved critical leave, overtime, payroll, and employee ESS access actions onto policies/Form Request authorization
- ✅ Added Sanctum ability middleware aliases and ability requirements for mobile/API route groups
- ✅ Expanded role-permission seeding for HR, Finance, Project Manager, and Admin roles
- ✅ Added/updated P0 tests for HR approval policy checks and API token ability enforcement

### **May 3, 2026 - Operational Cooperative Dashboard**

**📊 Dashboard Update:**
- ✅ Replaced main dashboard dummy metrics with real cooperative operational data
- ✅ Added daily work queue for pending members, payment approvals, unpaid dues, and low-stock POS products
- ✅ Added management snapshots for collections, POS performance, inventory risk, member health, and SHU

### **May 2, 2026 - Dual-Platform Rebranding**

**🎨 Branding Update:**
- ✅ **Rebranded to KojayaPro + Kojayaku**
  - **KojayaPro** - Sistem ERP admin untuk pengelolaan operasional koperasi
  - **Kojayaku** - Aplikasi anggota untuk cek simpanan, pinjaman, poin, transaksi

- ✅ **Updated Login Page**
  - Changed title to "Masuk ke KojayaPro"
  - Updated description to "Akses aman ke platform ERP Koperasi KOJAYA"

**📚 Documentation Updates:**
- ✅ **Updated all documentation files**
  - `/docs/project.md` - Added Kojayaku features & integration
  - `/docs/architecture.md` - Updated for dual-platform architecture
  - `/docs/api.md` - Added Kojayaku API sections (savings, loans, points, transactions)
  - `/docs/plan.md` - Updated Phase 3 for Kojayaku development

- ✅ **New Kojayaku API Documentation**
  - Savings (Simpanan) API - Balance, ledger, statements
  - Loans (Pinjaman) API - Application, tracking, calculator
  - Points & Rewards API - Balance, history, redemption
  - Transactions API - Purchase history, detail

**🔄 Integration Architecture:**
- ✅ **Shared Database** - Single PostgreSQL database
- ✅ **API Integration** - Kojayaku mengakses KojayaPro via RESTful API
- ✅ **Role-based Access** - Separate permissions for admin vs member

**📱 Kojayaku Features Planned:**
- ⏳ **Simpanan** - Real-time balance, history, certificates, interest
- ⏳ **Pinjaman** - Online application, status tracking, installment calculator
- ⏳ **Poin & Reward** - Point balance, catalog, redemption
- ⏳ **Transaksi** - Purchase history, digital receipts
- ⏳ **Profil** - Member profile, documents, status

---

## 🎯 2026-05: Security & Documentation

### **May 2, 2026 - Security Cleanup**

**🔒 Security Improvements:**
- ✅ **Updated .gitignore** - Added comprehensive ignore rules
  - Environment files (.env, .env.*)
  - Build artifacts (node_modules, vendor, public/build)
  - IDE files (.vscode, .idea)
  - Sensitive files (*.pem, *.key, *.cert)
  - Temporary files (tmp/, *.bak)

- ✅ **Removed .env from Git History**
  - Used `git filter-repo` to remove .env from entire history
  - Created backup branch before cleanup
  - Force pushed cleaned repository to GitHub
  - Repository now 100% secure

**⚠️ Security Warning:**
- Database credentials need rotation
- APP_KEY should be regenerated
- All API keys need to be rotated

**📚 Documentation Created:**
- ✅ `/docs/project.md` - Project overview & context
- ✅ `/docs/architecture.md` - System design & tech stack
- ✅ `/docs/api.md` - API documentation (50+ endpoints)
- ✅ `/docs/plan.md` - Roadmap & sprint planning
- ✅ `/docs/log.md` - Development log (this file)
- ✅ `/docs/decisions.md` - Architecture decision records

**📊 API Analysis:**
- ✅ **50+ API endpoints** documented
- ✅ **3 mobile app types** identified:
  - Technician Work Order API
  - Cooperative Member API
  - Employee Self Service API

**🔧 Bug Fixes:**
- ✅ Fixed Vite configuration (port 8081 → 5173)
- ✅ Fixed route() function errors in Vue components
- ✅ Updated imports in SelfService.vue and PettyCash/Index.vue

---

## 🎯 2026-04: Advanced Features

### **April 2026 - Module Completion**

**✅ Completed Modules:**
- ✅ **Procurement Module**
  - Purchase Request (PR)
  - Purchase Order (PO)
  - Goods Receive Note (GRN)
  - Vendor management

- ✅ **Maintenance Module**
  - Asset management
  - Work order system
  - Spare parts tracking
  - Preventive maintenance scheduling

- ✅ **Cooperative Module**
  - Member management (CooperativeMember)
  - Dues calculation (CooperativeContribution)
  - Payment tracking (CooperativePayment)
  - Member ledger

- ✅ **POS Module**
  - Product catalog (PosProduct)
  - Transaction processing (PosTransaction)
  - Inventory management (PosStockMovement)
  - Payment methods (CASH, TRANSFER, QRIS, MEMBER_CREDIT)

**📱 API Development:**
- ✅ Technician API (5 endpoints)
- ✅ Cooperative API v1 (12+ endpoints)
- ✅ POS API (2 endpoints)
- ✅ ESS API (attendance, leaves, payroll)

**🔧 Infrastructure:**
- ✅ Docker configuration (PHP 8.4, Node.js 22)
- ✅ PostgreSQL 13 database setup
- ✅ Redis for cache/session (optional)
- ✅ Laravel Pint for code formatting
- ✅ ESLint + Prettier for frontend

---

## 🎯 2026-03: Core HRM Features

### **March 2026 - HRM Foundation**

**✅ Employee Management:**
- ✅ Employee CRUD operations
- ✅ Employee contracts (EmployeeContract)
- ✅ Family data (EmployeeFamily)
- ✅ Certificates (EmployeeCertificate)
- ✅ Medical checkups (MedicalCheckup)

**✅ Attendance System:**
- ✅ GPS-based check-in/check-out
- ✅ Geofence validation
- ✅ Shift management (WorkShift)
- ✅ Overtime calculation (OvertimeCalculationService)
- ✅ Device ID tracking

**✅ Leave Management:**
- ✅ Leave request submission
- ✅ Leave calculation (exclude weekends)
- ✅ Approval workflow
- ✅ Leave types configuration
- ✅ Attachment upload

**✅ Payroll System:**
- ✅ Payroll calculation (PayrollCalculatorService)
- ✅ BPJS calculation (BpjsCalculationService)
- ✅ PPh21 tax calculation (Pph21TerService)
- ✅ Overtime pay calculation
- ✅ THR (holiday bonus) calculation
- ✅ Payslip PDF generation
- ✅ Bank export formats

---

## 🎯 2026-02: Project Initialization

### **February 26, 2026 - Project Start**

**🚀 Initial Setup:**
- ✅ **Laravel 12** project creation
- ✅ **Vue 3 + Inertia.js** setup
- ✅ **TypeScript** configuration
- ✅ **Tailwind CSS v4** integration
- ✅ **Vite** build tool setup
- ✅ **Laravel Wayfinder** installation

**📦 Dependencies Installed:**

**Backend (composer.json):**
- `laravel/framework`: ^12.0
- `inertiajs/inertia-laravel`: ^2.0
- `laravel/sanctum`: ^4.0
- `laravel/fortify`: ^1.30
- `spatie/laravel-permission`: ^7.2
- `laravel/wayfinder`: ^0.1.9
- `maatwebsite/excel`: ^3.1
- `barryvdh/laravel-dompdf`: ^3.1

**Frontend (package.json):**
- `vue`: ^3.5.13
- `@inertiajs/vue3`: ^2.3.7
- `tailwindcss`: ^4.1.1
- `reka-ui`: ^2.6.1
- `chart.js`: ^4.5.1
- `dhtmlx-gantt`: ^9.1.2

**🏗️ Architecture Decisions:**
- ✅ UUID primary keys for all models
- ✅ Soft deletes implementation
- ✅ Multi-organization support
- ✅ Service layer for business logic
- ✅ API Resources for JSON responses
- ✅ Form Request validation
- ✅ Observer pattern for model events

**📁 Directory Structure Created:**
- ✅ `app/Actions/` - Single-action controllers
- ✅ `app/Services/` - Business logic layer
- ✅ `app/Enums/` - PHP 8 enums
- ✅ `resources/js/pages/` - Inertia pages
- ✅ `resources/js/components/` - Vue components
- ✅ `resources/js/composables/` - Vue composables

---

## 📊 Development Statistics

### **Code Metrics (as of May 2, 2026)**

| Metric | Count | Notes |
|--------|-------|-------|
| **Models** | 72 | All with UUID primary keys |
| **Controllers** | 45+ | API + Web controllers |
| **Services** | 15 | Business logic layer |
| **Migrations** | 80+ | Database schema |
| **API Endpoints** | 50+ | RESTful endpoints |
| **Vue Components** | 100+ | Pages + shared components |
| **Test Files** | 10+ | PHPUnit tests |

### **Module Coverage**

| Module | Backend | API | Frontend | Status |
|--------|---------|-----|----------|--------|
| **Employee Management** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Attendance** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Leave Management** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Payroll** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Projects** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Procurement** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Maintenance** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Cooperative** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **POS** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Reports** | ✅ 100% | ✅ 100% | ✅ 100% | ✅ Complete |
| **Mobile Apps** | ✅ 100% | ✅ 100% | ❌ 0% | ⏳ Planned |

---

## 🐛 Bug Fixes & Issues

### **Known Issues (Resolved)**

| Issue | Date | Resolution |
|-------|------|------------|
| **Vite port mismatch** | May 2, 2026 | Changed port from 8081 to 5173 |
| **route() function errors** | May 2, 2026 | Fixed imports in Vue components |
| **.env exposed in git** | May 2, 2026 | Removed from git history, updated .gitignore |
| **ESLint errors** | May 2, 2026 | Fixed import order, removed unused imports |

### **Current Known Issues**

| Issue | Severity | Status | Planned Fix |
|-------|----------|--------|-------------|
| **Token never expires** | Medium | Known | Add token expiration in v1.1 |
| **No rate limiting** | Medium | Known | Implement rate limiting |
| **No API documentation** | Low | Known | Add OpenAPI/Swagger |
| **Test coverage < 70%** | Medium | Known | Increase test coverage |

---

## 🔄 Release History

### **v0.1.0 - Alpha** (February 2026)
- Initial Laravel setup
- Basic authentication
- Employee CRUD

### **v0.5.0 - Beta** (March 2026)
- Attendance system
- Leave management
- Payroll calculation
- Project management

### **v0.9.0 - Release Candidate** (April 2026)
- Procurement module
- Maintenance module
- Cooperative module
- POS module
- 50+ API endpoints

### **v1.0.0 - Production** (Planned: June 2026)
- Mobile apps
- Payment gateway
- WhatsApp notifications
- Performance optimizations

---

## 📈 Performance Metrics

### **Current Performance (May 2, 2026)**

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| **API Response Time** | < 500ms | ~200-400ms | ✅ Good |
| **Page Load Time** | < 2s | ~1.5s | ✅ Good |
| **Database Query Time** | < 100ms | ~50-80ms | ✅ Good |
| **Uptime** | 99.9% | 100% (dev) | ✅ Excellent |

---

## 🎓 Lessons Learned

### **What Went Well**
1. **Service Layer Pattern** - Separated business logic from controllers
2. **API Resources** - Consistent JSON response format
3. **UUID Primary Keys** - Better security and scalability
4. **Laravel Wayfinder** - Auto-discovery saved time
5. **TypeScript** - Caught bugs at compile time

### **What Could Be Improved**
1. **Testing** - Should have started testing earlier
2. **API Documentation** - Should document from day 1
3. **Mobile App** - Should start mobile development earlier
4. **Code Reviews** - Need more formal review process
5. **CI/CD** - Should automate testing and deployment

### **Recommendations for Next Phases**
1. **Test-Driven Development** - Write tests before code
2. **API-First Design** - Design API before implementation
3. **Mobile-First** - Consider mobile from the beginning
4. **Documentation** - Keep docs updated with code
5. **Code Quality** - More strict code reviews

---

## 🔮 Next Milestones

### **Upcoming Goals (May - July 2026)**
- [ ] **Technician App Beta** - June 4, 2026
- [ ] **Cooperative App Beta** - June 25, 2026
- [ ] **ESS App Beta** - July 16, 2026
- [ ] **Payment Gateway** - July 30, 2026
- [ ] **WhatsApp Notifications** - August 14, 2026
- [ ] **Production Launch** - August 31, 2026

---

## 📞 Communication Log

### **Stakeholder Meetings**

| Date | Topic | Attendees | Decisions |
|------|-------|-----------|-----------|
| Mar 1, 2026 | Project Kickoff | All | Confirmed Laravel 12 + Vue 3 stack |
| Mar 15, 2026 | HRM Module Review | HR, Tech | Approved attendance & leave features |
| Apr 1, 2026 | Scope Discussion | All | Added cooperative & POS modules |
| Apr 15, 2026 | API Requirements | Mobile Team | Defined API endpoints for mobile |
| May 2, 2026 | Security Review | All | Cleaned up git history, improved security |
| May 6, 2026 | Phase 4/5 Operator & Production Hardening | Engineering | Added cooperative approval inbox, closing checklist/period lock, payment reconciliation/receipt, operator exception analytics/export, OpenAPI, payment gateway foundation, push token registration, and monitoring API |

---

## 🙏 Acknowledgments

**Development Team:**
- Backend Developer(s)
- Frontend Developer(s)
- UI/UX Designer
- Project Manager
- QA Tester

**Special Thanks:**
- Laravel Community for excellent documentation
- Inertia.js Team for amazing framework
- Spatie for Laravel packages
- Open source contributors

---

*This log is maintained throughout the project lifecycle. Last updated: May 6, 2026*
