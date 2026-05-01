# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 + Vue 3 ERP/HRM system with Inertia.js frontend, handling modules for employee management, payroll, projects, procurement (PR/PO/GRN), maintenance, overtime, leave, invoicing, petty cash, budget management, and Indonesian tax compliance (eFaktur, BPJS, PPh21).

## Development Commands

### Backend
- `php artisan serve` - Start development server
- `php artisan test --compact` - Run all tests
- `php artisan test --compact tests/Feature/ExampleTest.php` - Run specific test file
- `php artisan test --compact --filter=testName` - Run specific test by name
- `vendor/bin/pint --dirty --format agent` - Format modified PHP files (required before committing changes)
- `composer run lint` - Run Pint linter (parallel mode)
- `composer run test` - Run full test suite with lint check
- `composer run dev` - Run all dev services (server, queue, logs, Vite) concurrently

### Frontend
- `npm run dev` - Start Vite dev server
- `npm run build` - Build for production
- `npm run build:ssr` - Build for production with SSR
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format with Prettier
- `npm run format:check` - Check formatting without fixing

### Setup
- `composer run setup` - Full project setup (composer, env, migrate, npm, build)
- `composer run dev:ssr` - Run all dev services with SSR (server, queue, logs, ssr)

## Architecture

### Backend Structure (Laravel 12)
- `app/Actions/` - Invokable controllers (Wayfinder pattern)
- `app/Models/` - Eloquent models (use casts() method, not $casts property)
- `app/Services/` - Business logic layer (payroll calculations, bank exports, reconciliations)
- `app/Http/Controllers/` - Traditional controllers
- `app/Http/Requests/` - Form Request validation classes
- `app/Http/Resources/` - Eloquent API resources
- `app/Jobs/` - Queued jobs
- `app/Observers/` - Model observers
- `app/Listeners/` - Event listeners
- `routes/web.php` - Web routes (Inertia pages + API endpoints for Inertia)
- `routes/api.php` - Pure API routes
- `bootstrap/app.php` - Middleware registration (no app/Http/Kernel.php in Laravel 12+)

### Frontend Structure (Vue 3 + TypeScript)
- `resources/js/pages/` - Inertia page components (auto-resolved by Vite)
- `resources/js/components/` - Reusable Vue components
- `resources/js/actions/` - Wayfinder-generated controller imports
- `resources/js/routes/` - Wayfinder-generated named route functions
- `resources/js/composables/` - Vue composables
- `resources/js/layouts/` - Inertia layout components
- `resources/js/api/` - API client utilities
- `resources/js/types/` - TypeScript type definitions

### Key Architecture Patterns

**Wayfinder Integration:**
- Import invokable controllers from `@/actions/` for single-action controllers
- Import named routes from `@/routes/` for route helpers
- Use `.form()` for Inertia Form submissions
- Pass `mergeQuery` to merge/remove URL parameters

**Services Layer:**
- Complex business logic lives in `app/Services/`
- Services handle: payroll calculations (PayrollCalculatorService), tax computations (Pph21TerService, BpjsCalculationService), bank exports (BankExportService), reconciliations (BankStatementReconciler), notifications (NotificationService), audit logging (AuditLogService)

**Approval Workflows:**
- Many entities use approval patterns (PayrollApproval, PurchaseRequest, PurchaseOrder, etc.)
- ApprovalLog model tracks approval history
- Check existing approval implementations before adding new workflows

**Indonesian Compliance:**
- BPJS calculations in BpjsCalculationService
- PPh21 tax calculations in Pph21TerService
- eFaktur submissions via DjpEfakturApiService
- Bank exports conform to Indonesian bank formats

**Data Import/Export:**
- Excel imports use `maatwebsite/excel` package with classes in `app/Imports/`
- Import classes implement `ToModel`, `WithHeadingRow`, `WithValidation` interfaces
- Use `updateOrCreate` in imports to handle duplicate data gracefully

**UUIDs & Polymorphism:**
- Most models use `HasUuids` trait for UUID primary keys (not auto-incrementing IDs)
- ApprovalLog uses polymorphic relations (`subject_type`, `subject_id`) to track any approvable entity

**Enums:**
- Use PHP 8 enums in `app/Enums/` for type-safe constants
- Enums include helper methods like `label()`, `color()` for display logic
- Example: CertificateStatus, CertificateType, McuResult

**Frontend Stack:**
- UI components: shadcn-vue in `resources/js/components/ui/` (auto-generated, run Prettier on changes)
- Icons: lucide-vue-next
- Charts: chart.js for data visualization
- Gantt charts: dhtmlx-gantt for project scheduling
- Composables: Shared logic in `resources/js/composables/` (e.g., useAppearance, useTwoFactorAuth)
- SSR: Supports server-side rendering with `resources/js/ssr.ts`
- TypeScript: Uses separate type imports (`import type`) enforced by ESLint

**Code Generation:**
- Wayfinder auto-generates `resources/js/actions/` and `resources/js/routes/` (ESLint-ignored)
- shadcn-vue components are auto-generated (ESLint-ignored)

**Domain Organization:**
- Services organized by domain: `app/Services/Procurement/`, `app/Services/Reports/`
- Reports organized by domain: `Attendance`, `Compliance`, `Leave`, `Payroll`
- Check existing implementations before adding new services or reports

## Testing

- Uses PHPUnit (not Pest)
- Tests in `tests/Feature/` and `tests/Unit/`
- Use model factories from `database/factories/`
- Use `fake()` or `$this->faker` for Faker data (check existing conventions)
- Always run related tests after making changes
- Test environment uses SQLite in-memory database
- Most services disabled in tests (Pulse, Telescope, Nightwatch)

## Code Quality

- Run `vendor/bin/pint --dirty --format agent` before finalizing PHP changes
- Run `npm run lint` before finalizing frontend changes
- All PHP methods must have explicit return type declarations
- Use PHP 8 constructor property promotion
- Always create Form Request classes for validation
- Follow existing code conventions when creating new files

## Key Dependencies

**Backend:**
- Laravel 12 (latest)
- Wayfinder (route/action generation)
- Spatie Laravel Permission (roles/permissions)
- Maatwebsite Excel (imports/exports)
- Barryvdh DOMPDF (PDF generation)
- Laravel Fortify (authentication)

**Frontend:**
- Vue 3 + TypeScript
- Inertia.js (SPA without API)
- Tailwind CSS v4
- Reka UI (headless components)
- shadcn-vue (UI component library)
- Chart.js (data visualization)
- dhtmlx-gantt (project scheduling)
