# Matriks Peran × Izin

Matriks ini merangkum izin utama per peran koperasi. Semua entri
diverifikasi terhadap `app/Enums/PermissionEnum.php` dan
`database/seeders/RolePermissionSeeder.php` (commit `20c86960`).

| Permission | Anggota | Admin Koperasi | Manajer Koperasi | Pengurus Koperasi |
| --- | --- | --- | --- | --- |
| `member_portal_access` | ✅ | — | — | — |
| `view_cooperative_member` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_member` | — | ✅ | — | ✅ |
| `validate_cooperative_member` | — | ✅ | — | ✅ |
| `verify_cooperative_member` | — | ✅ | — | — |
| `view_cooperative_loan` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_loan` | — | ✅ | — | — |
| `review_cooperative_loan` | — | — | ✅ | — |
| `approve_cooperative_loan` | — | — | — | ✅ |
| `manage_cooperative_loan_types` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_dues` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_payment` | — | ✅ | ✅ | ✅ |
| `access_cooperative_pos` | — | ✅ | ✅ | ✅ |
| `manage_pos_products` | — | ✅ | ✅ | ✅ |
| `manage_pos_categories` | — | ✅ | ✅ | ✅ |
| `view_pos_reports` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_points` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_rewards` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_redemption` | — | ✅ | ✅ | ✅ |
| `manage_cooperative_shu` | — | — | ✅ | ✅ |
| `view_cooperative_report` | — | ✅ | ✅ | ✅ |
| `view_audit_logs` | — | — | — | ✅ |
| `manage_cooperative_settings` | — | — | — | ✅ |
| `view_cooperative_all` | — | — | — | ✅ |
| `manage_cooperative_opening_balance` | — | ✅ | ✅ | ✅ |
| `approve_cooperative_opening_balance` | — | — | — | ✅ |
| `void_cooperative_opening_balance` | — | — | — | ✅ |
| `review_cooperative_resignation` | — | ✅ | ✅ | ✅ |
| `approve_pos_void` | — | ✅ | ✅ | ✅ |
| `view_store_credit` | — | ✅ | ✅ | ✅ |
| `manage_store_credit` | — | ✅ | ✅ | ✅ |
| `cashier_store_credit` | — | ✅ | — | — |

## Penetapan peran utama

`App\Services\Authorization\PrimaryRoleResolver` memilih peran
"utama" dengan urutan tetap:

1. `System Admin`
2. `Admin Pusat`
3. `Pengurus Koperasi`
4. `Manajer Koperasi`
5. `Admin Koperasi`
6. `Kasir Koperasi`

Pengguna dengan banyak peran (mis. `Anggota` + `Admin Koperasi`)
akan memperoleh peran koperasi utama dari `PrimaryRoleResolver`,
namun artikel dengan `roles: anggota` tetap dapat diakses karena
`ArticleRepository::resolveTargetRoles` mengembalikan seluruh
target role Spatie.

## Frontend

`resources/js/lib/role-experience.ts` memetakan peran ke
`RoleExperienceKey` dengan prioritas yang sama. Frontend
`useEffectiveExperience` adalah cerminan dari resolver backend.
