# Review Role Anggota

## Scope File Utama

- `database/seeders/RolePermissionSeeder.php`
- `app/Services/Auth/TokenAbilityResolver.php`
- `app/Services/Auth/Sso/GoogleSsoService.php`
- `app/Services/Cooperative/MemberValidationService.php`
- `app/Services/Cooperative/CooperativeMemberService.php`
- `app/Http/Middleware/EnsureIsMember.php`
- `app/Http/Middleware/EnsureMemberFullyActive.php`
- `app/Http/Controllers/MemberPortalController.php`
- `app/Http/Controllers/Api/V1/MemberSelfServiceController.php`
- `app/Http/Controllers/Api/V1/MemberStoreController.php`
- `app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php`
- `app/Http/Controllers/Cooperative/CooperativeMemberController.php`
- `app/Http/Controllers/Cooperative/MemberResignationController.php`
- `app/Http/Controllers/Cooperative/LoanController.php`
- `app/Policies/CooperativeMemberPolicy.php`
- `app/Policies/MemberResignationRequestPolicy.php`
- `app/Policies/LoanPolicy.php`
- `app/Exports/AnggotaExport.php`
- `routes/web.php`
- `routes/api.php`

## Model Akses Saat Ini

Role `Anggota` memperoleh:

```text
view_cooperative_member
view_cooperative_loan
```

Portal web anggota dilindungi oleh middleware `member`, sedangkan fitur finansial web memakai tambahan middleware `member.active`.

API mobile tidak memakai middleware status anggota. API hanya memeriksa:

- autentikasi Sanctum;
- ability `member:read` atau `member:write`;
- relasi `user->cooperativeMember` pada helper `memberOrAbort()`.

`TokenAbilityResolver` memberikan `member:read` dan `member:write` kepada setiap user yang memiliki relasi `cooperativeMember`, tanpa melihat status atau `validation_status`.

## Temuan

### MEMBER-01 — Critical: Export PII Seluruh Anggota Dapat Diakses Role Anggota

**Evidence**

- Seeder memberikan `view_cooperative_member` kepada role `Anggota`.
- Route `cooperative/members/export` berada di dalam group `can:view_cooperative_member`.
- `CooperativeMemberController::export()` hanya memanggil policy `viewAny`.
- `CooperativeMemberPolicy::viewAny()` mengizinkan user dengan `view_cooperative_member`.
- `AnggotaExport::query()` memulai query dari seluruh `CooperativeMember` tanpa scope pemilik atau organisasi.
- Export berisi NPWP, nomor telepon, autodebet, dan nomor rekening.

**Impact**

Anggota yang mengetahui URL dapat mengunduh data pribadi dan data rekening semua anggota. Ini merupakan kebocoran data pribadi dengan dampak operasional dan reputasi tinggi.

**Recommended fix**

1. Buat permission baru `export_cooperative_member` dan hanya berikan ke role administratif yang membutuhkan.
2. Pindahkan route export keluar dari group `view_cooperative_member`.
3. Tambahkan authorization eksplisit:

```php
Gate::authorize('export', CooperativeMember::class);
```

4. Tambahkan method policy `export()` yang hanya menerima role/permission admin.
5. Tetap tambahkan scope organisasi pada export walaupun route sudah diperketat.
6. Masking nomor rekening dan NPWP bila export tidak benar-benar memerlukan nilai penuh.

**Acceptance criteria**

- Role Anggota menerima HTTP 403 ketika memanggil export.
- Admin Koperasi yang berhak hanya mengekspor anggota dalam scope organisasi yang diizinkan.
- Test memverifikasi file export tidak berisi anggota di luar scope.

---

### MEMBER-02 — Critical: Pending, Rejected, Inactive, dan Resigned Masih Mendapat Member Abilities

**Evidence**

`TokenAbilityResolver` melakukan pengecekan berikut:

```php
if ($user->cooperativeMember) {
    $abilities[] = 'member:read';
    $abilities[] = 'member:write';
}
```

Tidak ada pengecekan terhadap:

- `status`;
- `validation_status`;
- role `Anggota`;
- tanggal resign;
- status soft-delete.

`MemberSelfServiceController::memberOrAbort()` hanya memastikan relasi anggota ada. Berbeda dengan web portal, controller ini tidak memakai ekuivalen `EnsureMemberFullyActive`.

**Endpoint berisiko**

- pengajuan penarikan simpanan;
- pengajuan pinjaman;
- restructure pinjaman;
- pembuatan payment intent;
- upload bukti pembayaran;
- pembacaan simpanan, pinjaman, SHU, transaksi, dan tagihan;
- support ticket dan aktivitas lain yang seharusnya mengikuti lifecycle anggota.

**Impact**

User yang masih pending, sedang revisi, sudah ditolak, dinonaktifkan, atau resign dapat terus menggunakan API member jika relasi anggota masih ada.

**Recommended fix**

Pisahkan endpoint menjadi dua kelompok:

#### Endpoint onboarding-safe

Boleh untuk status `PENDING`, `REVISION`, dan `PENDING_VALIDATION`:

- session/profile minimum;
- onboarding status;
- update data onboarding;
- status journey;
- notifikasi terkait onboarding;
- pengajuan ulang dokumen bila diperlukan.

#### Endpoint active-only

Wajib `validation_status === ACTIVE` dan `status === ACTIVE`:

- simpanan dan penarikan;
- tagihan dan pembayaran;
- pinjaman dan restructure;
- SHU;
- reward/points;
- transaksi POS;
- toko dan coffee order.

Buat middleware API, misalnya:

```php
'member.api' => EnsureApiMember::class,
'member.api.active' => EnsureApiMemberIsActive::class,
```

Jangan hanya memperbaiki controller satu per satu. Pasang middleware pada route group agar endpoint baru otomatis mengikuti aturan.

---

### MEMBER-03 — Critical: Token Tidak Dicabut Ketika Status Anggota Berubah

**Evidence**

- `MemberValidationService` menghapus role `Anggota` saat revisi, reject, atau setelah verifikasi admin.
- `CooperativeMemberService::resign()` hanya mengubah status menjadi `RESIGNED`.
- Tidak ada pemanggilan `tokens()->delete()` atau mekanisme downgrade abilities.
- Sanctum abilities disimpan pada saat token dibuat dan tidak otomatis berubah ketika role/permission berubah.
- Bahkan token baru masih mendapat `member:*` karena resolver hanya memeriksa keberadaan relasi anggota.

**Impact**

Perubahan status administratif tidak segera menghentikan akses mobile. Token yang sudah terbit tetap valid, dan login ulang dapat menghasilkan token member baru.

**Recommended fix**

1. Buat service terpusat, misalnya `MemberAccessRevocationService`.
2. Panggil service tersebut pada transition:
   - revision requested;
   - rejected;
   - deactivated;
   - resigned;
   - soft deleted;
   - perubahan user/member linking.
3. Hapus token member-app saja bila token diberi metadata/nama perangkat yang konsisten; untuk tahap awal, revoke seluruh token user lebih aman.
4. Resolver harus menolak member abilities untuk status selain `ACTIVE`, kecuali abilities onboarding khusus.
5. Tambahkan audit event `member_access_revoked`.

---

### MEMBER-04 — High: Permission Self-Service Dipakai untuk Halaman Admin

Permission `view_cooperative_member` dan `view_cooperative_loan` saat ini memiliki dua arti:

- anggota melihat data miliknya;
- operator/admin melihat daftar dan proses administrasi.

Akibatnya role Anggota dapat melewati route middleware area `cooperative/*`.

**Contoh dampak**

- membuka index anggota koperasi;
- membuka daftar pengajuan resign;
- membuka halaman daftar pinjaman;
- menerima response administratif yang tidak seluruh bagiannya di-scope ke anggota.

**Recommended permission split**

```text
member_portal_access
member_profile_read_own
member_profile_update_own
member_savings_read_own
member_payment_create_own
member_loan_apply_own
member_loan_read_own

cooperative_member_view
cooperative_member_manage
cooperative_member_export
cooperative_resignation_review
cooperative_loan_view_all
cooperative_loan_manage
cooperative_loan_review
cooperative_loan_approve
```

Role `Anggota` tidak perlu memiliki permission administratif `cooperative_*`. Self-service sebaiknya diatur melalui member middleware/policy khusus pemilik.

---

### MEMBER-05 — High: Daftar Pengajuan Resign Tidak Di-scope ke Pemilik

**Evidence**

- `MemberResignationRequestPolicy::viewAny()` menerima `COOPERATIVE_MEMBER_VIEW`.
- Role Anggota memiliki permission tersebut.
- `MemberResignationController::index()` mengambil seluruh `MemberResignationRequest` tanpa filter `member.user_id`.
- Statistik status juga dihitung global.
- Method policy `view()` menggunakan `viewAny() || owner`, sehingga user yang lolos `viewAny()` otomatis dapat melihat request milik siapa pun.

**Impact**

Anggota dapat membaca informasi pengunduran diri anggota lain, termasuk status, data anggota terkait, reviewer, dan statistik global.

**Recommended fix**

- Ubah `viewAny` menjadi khusus permission administratif seperti `review_cooperative_resignation`.
- Akses anggota terhadap resign miliknya harus melalui endpoint self-service yang selalu scope by member.
- Jangan menggunakan `viewAny()` sebagai shortcut di method `view()` bila permission tersebut juga dimiliki user biasa.

---

### MEMBER-06 — High: Halaman Pinjaman Membocorkan Data Pendukung Global

`LoanController::index()` memang membatasi query pinjaman utama ke pinjaman milik user bila tidak memiliki `view_cooperative_all` atau `manage_cooperative_loan`. Namun response tetap memuat:

- daftar seluruh anggota aktif untuk filter;
- daftar loan types;
- statistik global jumlah applied, manager approved, active, dan paid off.

Karena role Anggota memiliki `view_cooperative_loan`, halaman ini dapat dilewati secara permission.

**Recommended fix**

- Role Anggota tidak boleh masuk ke halaman admin pinjaman.
- Pisahkan halaman self-service dan admin sepenuhnya.
- Untuk defense-in-depth, scope semua props berdasarkan authorization yang sama dengan query utama.
- Jangan mengirim `members` dan global stats kepada user non-admin.

---

### MEMBER-07 — Medium: Profile Update Lintas Tabel Tidak Transactional

`MemberSelfServiceController::updateProfile()` memperbarui tabel `users` lalu `cooperative_members` secara terpisah.

**Risk**

Jika update kedua gagal, email/nama user dapat berbeda dari data member. Perbedaan ini berpengaruh pada SSO matching, notifikasi, dan proses admin.

**Recommended fix**

Gunakan `DB::transaction()` dan definisikan satu service untuk sinkronisasi user-member. Tambahkan test rollback ketika update member gagal.

---

### MEMBER-08 — Medium: PII Belum Dibatasi dan Dilindungi Secara Konsisten

`CooperativeMember` menyimpan:

- identity number;
- NPWP;
- nomor rekening;
- nama pemilik rekening;
- tanggal lahir;
- alamat.

Field tersebut tidak memakai encrypted cast. Beberapa halaman Inertia mengirim model langsung, dan export memakai nilai penuh.

**Recommended fix**

- Gunakan API Resource/DTO untuk seluruh response, termasuk Inertia props.
- Masking default: rekening hanya empat digit terakhir, NPWP sebagian, identity number sebagian.
- Full value hanya diberikan pada aksi yang memiliki permission khusus.
- Pertimbangkan encrypted casts untuk field yang tidak perlu dicari langsung.
- Bila pencarian diperlukan, gunakan blind index/hash terpisah.
- Tambahkan audit log saat full PII dilihat atau diekspor.

---

### MEMBER-09 — Medium: Store Order Rentan Race Condition

`MemberStoreController` mengecek stok saat membangun items, tetapi tidak melakukan reservation atau lock pada saat payment intent dibuat. Beberapa anggota dapat melihat stok yang sama dan membuat order bersamaan.

Pencarian idempotensi berdasarkan `metadata->client_reference` juga belum terlihat didukung unique database constraint.

**Recommended fix**

- Tambahkan unique key terstruktur, bukan hanya JSON metadata, misalnya `(cooperative_member_id, payable_type, client_reference)`.
- Gunakan transaction dan `lockForUpdate()` saat reservation.
- Tambahkan tabel/reservation status dengan expiry sesuai payment intent.
- Release reservation pada expired/failed payment.
- Settlement harus idempotent dan tidak boleh mengurangi stok dua kali.

---

### MEMBER-10 — Medium: `per_page` Tidak Selalu Dibatasi

Beberapa endpoint memakai nilai `per_page` langsung. User dapat meminta page size sangat besar dan meningkatkan penggunaan database/memory.

**Recommended fix**

Gunakan helper konsisten:

```php
$perPage = min(max($request->integer('per_page', 15), 1), 100);
```

Untuk endpoint yang memuat nested relations, batas 50 lebih aman.

## Hal yang Sudah Baik

- Ownership check tersedia pada detail invoice, payment, loan, receipt, dan payment intent.
- Receipt memakai temporary signed URL.
- `MemberStoreController` mengharuskan anggota aktif untuk order dan polling intent.
- Coffee order juga memakai active member query.
- Query self-service umumnya dimulai dari relasi milik member.
- API resource digunakan pada banyak response finansial.
- Web portal telah mempunyai konsep `member.active`; konsep ini dapat dijadikan acuan untuk API.

## Target Arsitektur Role Anggota

1. User login memperoleh token sesuai aplikasi.
2. Token member hanya memiliki abilities onboarding atau active-member, tidak wildcard.
3. Middleware API memvalidasi status pada setiap request, bukan hanya saat token dibuat.
4. Semua data anggota berasal dari `request->user()->cooperativeMember`.
5. Tidak ada permission administratif pada role Anggota.
6. Perubahan status segera mencabut token dan sesi yang tidak sesuai.
7. Setiap endpoint sensitif mempunyai negative authorization test untuk status pending, revision, rejected, inactive, dan resigned.