# Matriks Tanggung Jawab Peran

> **Status:** Otomatis dihasilkan oleh
> `node scripts/generate-role-matrix.mjs`.
> Sumber data: `resources/docs/user-guide/role-permissions.json`
> (Fase 10 dari correction pass).

Matriks ini mencantumkan izin (permission) yang diberikan oleh
`RolePermissionSeeder` kepada setiap peran koperasi. Sumber
data mesin-mesin (JSON) dibandingkan dengan implementasi Spatie
oleh `tests/Feature/Documentation/RolePermissionMatrixTest.php`.
Jika izin ditambah, dihapus, atau dipindahkan antar peran, JSON
harus diperbarui dan skrip ini dijalankan ulang.

Tabel: ✅ = izin diberikan, — = tidak diberikan.

| Izin | Admin Koperasi | Anggota | Manajer Koperasi | Pengurus Koperasi |
| --- | :-: | :-: | :-: | :-: |
| `access_cooperative_pos` | ✅ | — | ✅ | ✅ |
| `adjust_store_credit` | — | — | ✅ | ✅ |
| `approve_cooperative_loan` | — | — | — | ✅ |
| `approve_cooperative_member` | — | — | — | ✅ |
| `approve_cooperative_opening_balance` | — | — | — | ✅ |
| `approve_pos_void` | ✅ | — | ✅ | ✅ |
| `approve_store_credit_transfer` | — | — | ✅ | ✅ |
| `cashier_store_credit` | ✅ | — | ✅ | ✅ |
| `export_cooperative_member` | ✅ | — | — | ✅ |
| `export_cooperative_member_pii` | — | — | — | ✅ |
| `manage_cooperative_dues` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_ledger` | — | — | ✅ | ✅ |
| `manage_cooperative_loan` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_loan_types` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_member` | ✅ | — | — | ✅ |
| `manage_cooperative_opening_balance` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_payment` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_points` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_redemption` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_rewards` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_settings` | — | — | — | ✅ |
| `manage_cooperative_shu` | ✅ | — | ✅ | ✅ |
| `manage_pos_categories` | ✅ | — | ✅ | ✅ |
| `manage_pos_products` | ✅ | — | ✅ | ✅ |
| `manage_pos_shu` | — | — | ✅ | ✅ |
| `manage_store_credit` | ✅ | — | ✅ | ✅ |
| `manage_store_credit_limit` | — | — | ✅ | ✅ |
| `member_portal_access` | — | ✅ | — | — |
| `report_store_credit` | ✅ | — | ✅ | ✅ |
| `review_cooperative_loan` | — | — | ✅ | — |
| `review_cooperative_resignation` | ✅ | — | ✅ | ✅ |
| `update_cooperative_member_pii` | — | — | — | ✅ |
| `validate_cooperative_member` | ✅ | — | — | ✅ |
| `verify_cooperative_member` | ✅ | — | — | — |
| `view_cooperative_all` | — | — | — | ✅ |
| `view_cooperative_ledger` | ✅ | — | ✅ | ✅ |
| `view_cooperative_loan` | ✅ | — | ✅ | ✅ |
| `view_cooperative_member` | ✅ | — | ✅ | ✅ |
| `view_cooperative_member_pii` | — | — | — | ✅ |
| `view_cooperative_report` | — | — | ✅ | ✅ |
| `view_pos_reports` | ✅ | — | ✅ | ✅ |
| `view_store_credit` | ✅ | — | ✅ | ✅ |
| `view_store_credit_all` | — | — | — | ✅ |
| `void_cooperative_opening_balance` | — | — | — | ✅ |
| **Jumlah izin** | 26 | 1 | 29 | 41 |
