# User Management & PRD Alignment Implementation Plan

Implement a premium User Management system that fully aligns with the ERP PRD (Multi-Branch Support and 10 Specific Roles).

## User Review Required

> [!IMPORTANT]
> - **Database Schema Change**: We will introduce the `organizations` table to support the Multi-Cabang hierarchy (PRD 3.5.5) and add `organization_id` to the `users` table.
> - **Seeder Overhaul**: The `RolePermissionSeeder` will be completely rewritten to generate the 10 roles specified in the PRD (from System Admin to Karyawan Outsourcing).
> - **Data Isolation Warning**: For now, the UX will permit System Admin to see all users, preparing the exact UI inputs for when Organization filtering is strictly enforced in queries.

## Proposed Changes

### Database & Models
- **[NEW] [CreateOrganizationsTable](file:///home/john-d/Documents/erp-project/erp-app/database/migrations/xxxx_xx_xx_xxxxxx_create_organizations_table.php)**: Migration for `organizations` (id, parent_id, name, type, level, etc.).
- **[MODIFY] [CreateUsersTable](file:///home/john-d/Documents/erp-project/erp-app/database/migrations/0001_01_01_000000_create_users_table.php)**: Add `organization_id` foreign key.
- **[NEW] [Organization](file:///home/john-d/Documents/erp-project/erp-app/app/Models/Organization.php)**: Eloquent model with self-referencing relationship (parent/children) and hasMany Users.
- **[MODIFY] [User](file:///home/john-d/Documents/erp-project/erp-app/app/Models/User.php)**: Add `organization_id` fillable and `organization()` relationship.
- **[MODIFY] [RolePermissionSeeder](file:///home/john-d/Documents/erp-project/erp-app/database/seeders/RolePermissionSeeder.php)**: Create 10 PRD roles, seed a default Organization (Pusat), and assign it to the Super Admin.

### Backend Routing & Controllers
- **[MODIFY] [web.php](file:///home/john-d/Documents/erp-project/erp-app/routes/web.php)**: Register resource routes for `users` and `roles`.
- **[NEW] [RoleController](file:///home/john-d/Documents/erp-project/erp-app/app/Http/Controllers/RoleController.php)**: View-only role list and detailed permission toggling (managing roles might be restricted by PRD).
- **[NEW] [UserController](file:///home/john-d/Documents/erp-project/erp-app/app/Http/Controllers/UserController.php)**: Manage users, assign roles, and associate with an Organization limit.

### Frontend Components & Navigation
- **[MODIFY] [navigation.ts](file:///home/john-d/Documents/erp-project/erp-app/resources/js/types/navigation.ts)**: Extend `NavItem` with an `items` array for dropdown menus.
- **[MODIFY] [NavMain.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/components/NavMain.vue)**: Utilize `SidebarMenuSub` for rendering nested children links.
- **[MODIFY] [AppSidebar.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/components/AppSidebar.vue)**: Insert "User Management" as a parent item with "Users" and "Roles" children.

### UI Pages (Premium Tailwind v4)
- **[NEW] [User/Index.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/pages/User/Index.vue)**: Beautiful datatable for Users showing Avatar, Name, Role Badge, and Organization.
- **[NEW] [User/Edit.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/pages/User/Edit.vue)**: Complex form allowing selection of 1 of 10 Roles and assignment to an Organization tree.
- **[NEW] [Role/Index.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/pages/Role/Index.vue)**: Grid of cards explaining what each of the 10 PRD roles does, with active user counts.
- **[NEW] [Role/Edit.vue](file:///home/john-d/Documents/erp-project/erp-app/resources/js/pages/Role/Edit.vue)**: Visual switch-based menu grouped by modules (HRM, Maintenance, Finance) for managing specific Spatie Permissions overriding/granting per role.

## Verification Plan

### Automated Tests
- **[NEW] [UserRoleIntegrationTest.php](file:///home/john-d/Documents/erp-project/erp-app/tests/Feature/UserRoleIntegrationTest.php)**: 
  - Test user creation with `organization_id`.
  - Check that all 10 PRD roles exist after seeding.
- **Commands**:
  - `php artisan test --filter=UserRoleIntegrationTest`

### Manual Verification
1. Run `php artisan migrate:fresh --seed` to rebuild DB with `organizations` and the 10 PRD roles.
2. Login as `admin@erp.com`.
3. Check Sidebar to verify the collapsible "User Management" menu works.
4. Open the **Roles** page and verify the 10 specific roles (Admin Pusat, HR Pusat, Site Manager, etc.) are listed aesthetically.
5. Create a new User, ensuring you can pick their role and assign them to an organization.
