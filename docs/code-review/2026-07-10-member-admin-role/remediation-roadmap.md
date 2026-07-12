# Roadmap Remediasi Role Anggota dan Admin Koperasi

## Tujuan

Roadmap ini mengubah hasil review menjadi urutan pekerjaan yang dapat dikerjakan tanpa melakukan redesign besar sekaligus.

Prinsip implementasi:

1. Tutup kebocoran akses terlebih dahulu.
2. Pertahankan kompatibilitas aplikasi Android dan web selama migrasi.
3. Pindahkan invariant penting ke middleware, policy, dan domain service—bukan hanya UI.
4. Setiap perubahan authorization wajib disertai negative test.
5. Hindari perubahan role/permission langsung di production tanpa migration dan observability.

## P0 — Security Hotfix

Target: harus selesai sebelum backend digunakan untuk data anggota production.

### P0.1 Tutup Export Daftar Anggota

**Perubahan**

- Tambahkan permission `export_cooperative_member`.
- Berikan hanya kepada role yang benar-benar membutuhkan.
- Pindahkan route export dari group `view_cooperative_member`.
- Tambahkan method policy `export()`.
- Scope query export berdasarkan organisasi.
- Masking atau hapus NPWP dan rekening bila tidak diperlukan.

**File kandidat**

- `app/Enums/PermissionEnum.php`
- `database/seeders/RolePermissionSeeder.php`
- `app/Policies/CooperativeMemberPolicy.php`
- `app/Http/Controllers/Cooperative/CooperativeMemberController.php`
- `app/Exports/AnggotaExport.php`
- `routes/web.php`

**Definition of done**

- Anggota menerima 403.
- Admin tanpa permission export menerima 403.
- Export hanya berisi anggota dalam scope yang diizinkan.
- Field sensitif mengikuti kebutuhan bisnis yang disetujui.

---

### P0.2 Tambahkan Active-Member Gate untuk API Mobile

**Perubahan**

Buat middleware:

```text
member.api
member.api.active
```

Kelompokkan route:

#### Onboarding-safe

- dashboard/status minimum;
- onboarding status dan step;
- status journey;
- profile onboarding;
- resignation status sesuai keputusan bisnis;
- notification onboarding.

#### Active-only

- savings;
- withdrawal;
- dues dan bills;
- payment intent dan payment proof;
- loans dan restructure;
- SHU;
- points/rewards;
- POS transactions;
- coffee/store order.

Middleware wajib mengecek `status` dan `validation_status`, bukan salah satunya saja secara fallback.

**Definition of done**

- Pending/revision/rejected/inactive/resigned tidak dapat memakai endpoint finansial.
- Active member tetap kompatibel dengan aplikasi Android saat ini.
- Response API konsisten, misalnya 403 dengan error code `MEMBER_NOT_ACTIVE`.

---

### P0.3 Perbaiki Token Ability Resolver

**Perubahan**

- Hilangkan early return wildcard sebelum app scope.
- Jangan terbitkan `member:write` bila anggota tidak aktif.
- Tambahkan abilities onboarding terpisah bila dibutuhkan.
- Parameter `app` harus divalidasi dengan enum/allowlist.
- Default app tidak boleh menghasilkan token yang lebih luas daripada aplikasi yang diminta.

Contoh target:

```text
profile:read
member:onboarding:read
member:onboarding:write
member:self:read
member:self:write
```

Untuk admin koperasi, gunakan ability domain granular pada fase P1.

**Definition of done**

- System Admin login dengan `app=member` tidak memperoleh `*`.
- Pending member tidak memperoleh active-member abilities.
- Test snapshot abilities tersedia untuk seluruh role utama.

---

### P0.4 Revoke Token pada Perubahan Lifecycle

Buat `MemberAccessRevocationService` dan panggil pada:

- request revision;
- reject;
- deactivate;
- resign;
- delete/archive;
- unlink user-member;
- perubahan role yang menghilangkan akses anggota.

Untuk tahap awal, revoke seluruh token user. Setelah token metadata lebih matang, revoke berdasarkan token audience/app.

**Definition of done**

- Token lama langsung menerima 401 setelah transition.
- Audit log mencatat actor, alasan, dan jumlah token yang dicabut.
- Proses berada dalam transaction atau dijalankan aman setelah commit.

---

### P0.5 Scope Pengajuan Resign dan Halaman Admin yang Dapat Dimasuki Anggota

**Perubahan**

- Role Anggota tidak lagi memakai permission admin untuk resign/loan/member list.
- `MemberResignationRequestPolicy::viewAny()` hanya untuk reviewer/admin.
- Hilangkan global stats dan member lookup dari response non-admin.
- Pertahankan endpoint self-service khusus milik anggota.

**Definition of done**

- Anggota A tidak dapat mengetahui request, statistik, atau data Anggota B.
- Direct URL ke halaman admin menghasilkan 403.

---

### P0.6 Hilangkan Kredensial Admin Default

**Perubahan**

- Pisahkan seeder role-permission dari seeder demo user.
- Admin production dibuat melalui command/bootstrap secret.
- Tambahkan check deployment untuk menolak akun `admin@erp.com` dengan password default.

**Definition of done**

- Menjalankan seeder production tidak membuat akun dengan kredensial statis.
- Dokumentasi bootstrap admin tersedia.

## P1 — Authorization Model Refactor

Target: satu sprint setelah hotfix.

### P1.1 Pisahkan Permission Self-Service dan Administrasi

Permission yang disarankan:

```text
member_portal_access
member_profile_read_own
member_profile_update_own
member_savings_read_own
member_savings_withdraw_own
member_dues_read_own
member_payment_create_own
member_loan_read_own
member_loan_apply_own
member_reward_redeem_own

view_cooperative_member
manage_cooperative_member
verify_cooperative_member
approve_cooperative_member
export_cooperative_member
review_cooperative_resignation
```

**Migration strategy**

1. Tambahkan permission baru tanpa menghapus yang lama.
2. Update policy dan route agar menerima permission baru.
3. Update seeder dan role assignment.
4. Jalankan authorization test.
5. Audit database role-permission production.
6. Hapus alias lama setelah satu release stabil.

Role `Anggota` idealnya hanya memperoleh permission `*_own` atau cukup `member_portal_access`, sementara ownership ditegakkan oleh member policy/middleware.

---

### P1.2 Granular Sanctum Abilities

Ganti ability umum dengan domain-specific abilities.

Contoh mapping:

| Permission | Ability |
|---|---|
| `view_cooperative_member` | `cooperative.member.read` |
| `manage_cooperative_member` | `cooperative.member.write` |
| `verify_cooperative_member` | `cooperative.member.verify` |
| `approve_cooperative_member` | `cooperative.member.approve` |
| `manage_cooperative_dues` | `cooperative.dues.write` |
| `manage_cooperative_payment` | `cooperative.payment.write` |
| `review_cooperative_loan` | `cooperative.loan.review` |
| `approve_cooperative_loan` | `cooperative.loan.approve` |

Route middleware dan controller policy harus sama-sama memverifikasi intent akses.

---

### P1.3 Terapkan Organization Scope

Buat kontrak yang konsisten:

```php
interface OrganizationScoped
{
    public function scopeVisibleTo(Builder $query, User $user): Builder;
}
```

Atau gunakan query service per domain.

Scope harus digunakan pada:

- list;
- detail;
- dropdown;
- statistik;
- export;
- batch;
- job/queue;
- report.

Untuk user pusat, akses lintas organisasi harus memakai permission eksplisit seperti `view_cooperative_all`.

---

### P1.4 Maker-Checker di Domain Service

Tambahkan actor separation pada:

- verifikasi dan approval anggota;
- review dan approval pinjaman;
- maker dan approver pembayaran;
- opening balance draft/post/void;
- POS void request/process;
- koreksi ledger.

State transition harus menolak actor yang sama, kecuali emergency override yang diaudit.

---

### P1.5 Standardisasi Authorization Layer

Untuk setiap endpoint:

1. Route ability/permission: coarse access.
2. FormRequest authorization: validasi actor bila relevan.
3. Policy: object-level access.
4. Domain service: state transition dan maker-checker.
5. Query scope: data isolation.

Hindari controller yang hanya memakai salah satu lapisan untuk operasi sensitif.

## P2 — Data Protection dan Reliability

### P2.1 PII Protection

- API Resource/DTO untuk list dan detail.
- Mask rekening, NPWP, identity number secara default.
- Permission khusus untuk melihat full PII.
- Audit full-view dan export.
- Evaluasi encrypted casts dan blind index.
- Hindari raw model dalam Inertia props.

### P2.2 Transactional User-Member Sync

Pindahkan update profile dan provisioning ke service transaction.

Invariant:

- email user dan member konsisten;
- role/linking berubah atomic;
- failure melakukan rollback penuh;
- perubahan email yang terkait SSO melalui flow verifikasi khusus.

### P2.3 Store Order Reservation dan Idempotensi

- Tambahkan kolom `client_reference` first-class dan unique index.
- Lock/reserve stock ketika intent dibuat.
- Reservation memiliki expiry.
- Settlement idempotent.
- Release reservation saat payment gagal/kedaluwarsa.
- Tambahkan concurrency test.

### P2.4 Batas Pagination dan Query Cost

- Helper page size global dengan batas 50/100.
- Batasi filter/search yang mahal.
- Tambahkan index database untuk filter utama.
- Hindari memuat seluruh bills/transactions ke collection sebelum pagination untuk data besar.

### P2.5 Hapus Jalur Opening Balance Legacy

- Satu jalur write melalui draft/post batch.
- API create/update member tidak menulis ledger legacy.
- Migrasi dan rekonsiliasi entry lama.
- Tambahkan report exception untuk opening balance tanpa batch.

### P2.6 Audit Contract

Event sensitif minimal mencatat:

```text
correlation_id
actor_user_id
actor_roles
organization_id
action
subject_type
subject_id
old_state
new_state
reason
ip_address
user_agent
occurred_at
```

Audit write tidak boleh membocorkan secret, password, token, QR payload, atau dokumen penuh.

## Urutan Pull Request yang Disarankan

1. `fix/security-member-export-scope`
2. `fix/api-active-member-gate`
3. `fix/sanctum-token-scope-and-revocation`
4. `fix/member-resignation-admin-isolation`
5. `chore/remove-default-admin-credential`
6. `refactor/member-vs-admin-permissions`
7. `refactor/granular-cooperative-abilities`
8. `feat/cooperative-organization-scope`
9. `feat/cooperative-maker-checker`
10. `feat/pii-masking-and-audit`
11. `feat/store-stock-reservation`

PR P0 sebaiknya kecil dan tidak digabung dengan refactor permission besar agar mudah direview dan di-rollback.

## Rollout Aman

### Sebelum deploy

- backup database;
- export matriks role-permission production;
- inventaris token aktif;
- jalankan seluruh authorization test;
- verifikasi aplikasi Android dengan akun active dan pending;
- verifikasi admin web untuk role Admin, Manajer, Pengurus, dan Kasir.

### Saat deploy

- jalankan migration permission;
- clear permission cache;
- revoke token yang tidak dapat dipetakan dengan aman;
- pantau 401/403 rate;
- pantau audit log transition;
- sediakan rollback permission assignment.

### Setelah deploy

- cek tidak ada export oleh role anggota;
- cek pending member tidak bisa mengakses endpoint finansial;
- sampling data organisasi;
- review akun dengan wildcard/overprivileged permission;
- rotasi kredensial admin dan integration token.

## Definition of Done Keseluruhan

Remediasi dianggap selesai ketika:

- tidak ada route admin yang dapat dimasuki role Anggota;
- seluruh resource anggota ter-scope pemilik atau organisasi;
- status anggota nonaktif tidak memiliki akses finansial;
- perubahan lifecycle mencabut token;
- mobile token tidak pernah wildcard;
- maker dan checker berbeda untuk proses yang diwajibkan;
- PII dimasking secara default;
- setiap role mempunyai positive dan negative authorization test;
- seluruh perubahan tercatat pada audit log;
- dokumentasi role-permission sesuai dengan seeder dan production database.