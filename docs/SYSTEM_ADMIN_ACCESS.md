# System Admin (Superadmin) Access Documentation

**Updated:** 2026-05-06

---

## Overview

User dengan role **System Admin** dan **Admin Pusat** adalah **superadmin** yang memiliki akses penuh ke semua fitur aplikasi tanpa batasan.

---

## Access Credentials

**Email:** `admin@erp.com`
**Password:** `password` (⚠️ **UBAH setelah first login untuk security!**)

---

## Access Levels

### 1. **Backend Authorization (Spatie Permissions)**
- ✅ **ALL 105 permissions** assigned via `syncPermissions()`
- ✅ Location: `database/seeders/RolePermissionSeeder.php:52-53`
- ✅ Verified by test: `SystemAdminAccessTest::test_system_admin_has_all_permissions_via_spatie()`

### 2. **Gate/Policy Bypass**
- ✅ **Bypasses ALL Gate checks** via `Gate::before`
- ✅ Location: `app/Providers/AppServiceProvider.php`
- ✅ Code: `Gate::before(fn ($user): ?bool => $user->hasRole('System Admin') ? true : null);`
- ✅ Verified by test: `SystemAdminAccessTest::test_system_admin_bypasses_gates()`

### 3. **Mobile API Token Abilities**
- ✅ **Wildcard ability `*`** untuk semua mobile API endpoints
- ✅ Location: `app/Http/Controllers/Api/AuthController.php:81-83`
- ✅ Code:
  ```php
  if ($user->hasAnyRole(['System Admin', 'Admin Pusat'])) {
      return ['*']; // Wildcard ability!
  }
  ```

### 4. **Frontend Sidebar Menu**
- ✅ **Shows ALL menu items** without permission filtering
- ✅ Location: `resources/js/components/AppSidebar.vue:87-91`
- ✅ Code:
  ```javascript
  const isSystemAdmin = computed(() =>
    userRoles.value.includes("System Admin") || userRoles.value.includes("Admin Pusat"),
  );
  const canAccess = (permissions?: string | string[]): boolean => {
    if (isSystemAdmin.value) {
      return true; // System Admin has access to everything!
    }
    // ... rest of permission logic
  };
  ```
- ✅ Verified by test: `SystemAdminAccessTest::test_system_admin_can_access_all_web_pages()`

---

## What System Admin Can Access

### **Cooperative Features**
- ✅ Anggota (Members management)
- ✅ Iuran & Simpanan (Dues & Savings)
- ✅ Simpan Pinjam (Loans)
- ✅ Poin & Reward (Points & Rewards)
- ✅ POS Toko (POS & Sales)
- ✅ Inventory POS (Products & Categories)
- ✅ Laporan Koperasi (Cooperative Reports)
- ✅ SHU Koperasi (Cooperative SHU)
- ✅ Operator Dashboard (Approval Inbox, Exceptions, Closing, Reconciliation)

### **HR/ESS Features**
- ✅ Attendance Tracker
- ✅ Leave Management
- ✅ Overtime Management
- ✅ Payroll
- ✅ HR Master Data (Employee, Departments, Positions, Job Grades, etc.)
- ✅ ESS Portal Access

### **Finance Features**
- ✅ Invoices
- ✅ RKAP/Budgets
- ✅ Petty Cash
- ✅ Reimbursements
- ✅ Bank Batches
- ✅ Bank Reconciliation
- ✅ Chart of Accounts
- ✅ Journal Entries
- ✅ Trial Balance
- ✅ Balance Sheet
- ✅ Income Statement
- ✅ E-Faktur

### **Procurement**
- ✅ Purchase Requests
- ✅ Purchase Orders
- ✅ Goods Receive (GRN)
- ✅ Vendors

### **Asset Management**
- ✅ Assets
- ✅ Work Orders
- ✅ Spare Parts
- ✅ Warehouses

### **Projects**
- ✅ All Projects
- ✅ Clients

### **System & Administration**
- ✅ Organizations
- ✅ Users
- ✅ Roles & Permissions
- ✅ Reports
- ✅ Audit Logs

---

## How to Test System Admin Access

### **1. Login as System Admin**
```bash
# Via browser
URL: http://your-app.com/login
Email: admin@erp.com
Password: password
```

### **2. Verify Permissions**
```bash
# Via tinker
php artisan tinker
>>> $user = \App\Models\User::where('email', 'admin@erp.com')->first;
>>> $user->getAllPermissions()->count(); // Should return 105
>>> $user->can('manage_users'); // true
>>> $user->can('view_cooperative_all'); // true
>>> $user->can('view_audit_logs'); // true
```

### **3. Run Tests**
```bash
php artisan test --filter=SystemAdminAccessTest
# Expected: 6 passed, 1 incomplete
```

---

## Troubleshooting

### **Issue: Sidebar menu tidak muncul semua**
**Solution:** Pastikan permissions sudah di-seed:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

### **Issue: Forbidden saat akses halaman tertentu**
**Solution:** Pastikan `Gate::before` sudah ada di `AppServiceProvider.php`:
```php
Gate::before(fn ($user): ?bool => $user->hasRole('System Admin') ? true : null);
```

### **Issue: Mobile API return 403**
**Solution:** Pastikan `AuthController.php` line 81-83 sudah ada:
```php
if ($user->hasAnyRole(['System Admin', 'Admin Pusat'])) {
    return ['*'];
}
```

---

## Security Recommendations

1. ⚠️ **UBAH PASSWORD** admin@erp.com setelah first login
2. ⚠️ Gunakan 2FA untuk System Admin account
3. ⚠️ Batasi System Admin login hanya dari IP tertentu (production)
4. ⚠️ Audit semua System Admin activities via Audit Logs
5. ⚠️ Consider menggunakan email pribadi, bukan admin@erp.com

---

## Related Files

- `database/seeders/RolePermissionSeeder.php` - Role & permission seeding
- `app/Providers/AppServiceProvider.php` - Gate::before bypass
- `app/Http/Controllers/Api/AuthController.php` - Mobile API wildcard ability
- `resources/js/components/AppSidebar.vue` - Frontend sidebar menu logic
- `tests/Feature/SystemAdminAccessTest.php` - System Admin access tests

---

## Summary

**System Admin** adalah superadmin dengan:
- ✅ All 105 Spatie permissions
- ✅ Bypass all Gate/Policy checks
- ✅ Wildcard `*` token ability for mobile API
- ✅ Access to ALL sidebar menu items

**Status:** ✅ **Fully Implemented & Tested**
