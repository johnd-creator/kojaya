# Review Role Admin Koperasi

## Scope Role

Review ini mencakup role koperasi berikut:

- `Admin Koperasi`;
- `Pengurus Koperasi`;
- `Manajer Koperasi`;
- `Kasir Koperasi`;
- `System Admin` dan `Admin Pusat` saat mengakses modul koperasi.

File utama yang ditinjau:

- `database/seeders/RolePermissionSeeder.php`
- `app/Enums/PermissionEnum.php`
- `app/Services/Auth/TokenAbilityResolver.php`
- `app/Policies/*`
- `app/Http/Controllers/Cooperative/*`
- `app/Http/Controllers/Api/V1/*`
- `app/Http/Requests/Cooperative/*`
- `routes/web.php`
- `routes/api.php`

## Matriks Tanggung Jawab Saat Ini

### Admin Koperasi

Memiliki akses luas untuk:

- melihat dan mengelola anggota;
- verifikasi administrasi anggota;
- iuran dan pembayaran;
- pinjaman;
- POS, produk, kategori, points, reward, dan redemption;
- ledger;
- opening balance.

### Pengurus Koperasi

Memiliki cakupan paling luas pada domain koperasi, termasuk:

- approval final anggota;
- approval pinjaman;
- laporan;
- SHU;
- void opening balance;
- pengelolaan ledger dan konfigurasi koperasi.

### Manajer Koperasi

Berfokus pada operasional dan review pinjaman, tetapi juga memperoleh banyak akses transaksi, POS, reward, ledger, dan opening balance.

### Kasir Koperasi

Memiliki akses anggota, payment, pinjaman read, POS, dan laporan.

Pembagian role ini sudah mencerminkan pemisahan tugas pada level tinggi, tetapi implementasi teknis masih memiliki beberapa jalur yang terlalu luas atau tidak konsisten.

## Temuan

### ADMIN-01 — High: Token Admin Sistem Menghasilkan Wildcard Sebelum Pembatasan Aplikasi

`TokenAbilityResolver::for()` langsung mengembalikan `['*']` ketika user memiliki salah satu permission:

- `manage_organizations`;
- `manage_users`;
- `manage_roles`.

Early return ini dijalankan sebelum filtering berdasarkan parameter aplikasi (`member`, `ess`, atau `technician`).

**Impact**

System Admin atau Admin Pusat yang login melalui aplikasi member dapat menerima token wildcard, bukan token terbatas untuk aplikasi tersebut. Bila token mobile bocor, seluruh API yang memakai Sanctum ability dapat dilewati oleh token itu.

**Recommended fix**

- Jangan memakai wildcard untuk personal access token aplikasi mobile.
- Hitung permissions terlebih dahulu, lalu selalu terapkan allowlist berdasarkan aplikasi.
- Bila wildcard masih diperlukan untuk integrasi internal, buat flow/token issuer terpisah dengan audience, device class, expiry, dan audit khusus.
- Gunakan token abilities eksplisit untuk admin web/mobile, misalnya `cooperative.member.read`, `cooperative.payment.approve`, dan seterusnya.

Contoh arah implementasi:

```php
public function for(User $user, ?string $app): array
{
    $abilities = $this->resolveAbilitiesFromPermissions($user);

    return match ($app) {
        'member' => $this->only($abilities, self::MEMBER_APP_ABILITIES),
        'cooperative-admin' => $this->only($abilities, self::COOPERATIVE_ADMIN_ABILITIES),
        'ess' => $this->only($abilities, self::ESS_ABILITIES),
        default => $this->safeDefaultAbilities($abilities),
    };
}
```

---

### ADMIN-02 — High: `cooperative:read` dan `cooperative:write` Terlalu Kasar

Saat ini berbagai permission domain digabung menjadi dua ability umum:

```text
cooperative:read
cooperative:write
```

Satu permission seperti `manage_cooperative_member` dapat menghasilkan `cooperative:write`, lalu token tersebut melewati route iuran, pembayaran, dan pinjaman yang juga hanya memeriksa ability umum. Beberapa controller melakukan pengecekan permission lanjutan, tetapi konsistensinya bergantung pada implementasi masing-masing controller.

**Risk**

Endpoint baru mudah lupa menambahkan policy/permission internal. Middleware route memberi kesan aman padahal ability-nya melampaui domain asal user.

**Recommended abilities**

```text
cooperative.member.read
cooperative.member.write
cooperative.member.verify
cooperative.member.approve
cooperative.member.export
cooperative.dues.read
cooperative.dues.generate
cooperative.payment.read
cooperative.payment.record
cooperative.payment.approve
cooperative.loan.read
cooperative.loan.review
cooperative.loan.approve
cooperative.loan.disburse
cooperative.ledger.read
cooperative.ledger.write
cooperative.report.read
cooperative.pos.read
cooperative.pos.write
```

Tetap gunakan policy/controller authorization sebagai defense-in-depth. Ability tidak menggantikan policy.

---

### ADMIN-03 — High/Medium: Scope Organisasi Belum Diterapkan Secara Konsisten

Model `User` dan banyak model koperasi memiliki `organization_id`. `BasePolicy` bahkan menyediakan helper `sameOrganization()`, tetapi policy anggota dan banyak query administratif tidak menggunakannya.

User dengan permission manage umumnya dapat membaca atau mengubah record seluruh organisasi.

**Severity**

- **High** bila satu deployment dipakai beberapa koperasi/unit organisasi yang datanya harus terisolasi.
- **Medium** bila aplikasi secara permanen hanya memiliki satu koperasi pusat.

**Recommended fix**

1. Definisikan tenancy rule secara eksplisit: single cooperative, hierarchical organization, atau strict tenant isolation.
2. Buat query scope terpusat, misalnya `visibleTo(User $user)`.
3. Gunakan policy yang memeriksa permission **dan** organization scope.
4. Terapkan scope pada:
   - index dan detail;
   - export;
   - statistik/dashboard;
   - lookup dropdown;
   - batch processing;
   - report dan background job.
5. Jangan menerima `organization_id` dari client untuk proses sensitif kecuali user memang dapat memilih organisasi tersebut.

Contoh:

```php
public function update(User $user, CooperativeMember $member): bool
{
    return $user->can('manage_cooperative_member')
        && $this->sameOrganization($user, $member);
}
```

Untuk role pusat, gunakan permission eksplisit `view_cooperative_all`, bukan pengecualian implisit.

---

### ADMIN-04 — Medium: Maker-Checker Belum Mencegah Aktor yang Sama

Workflow anggota sudah membedakan:

- Admin Koperasi melakukan verifikasi administrasi;
- Pengurus Koperasi melakukan approval final.

Namun service tidak memastikan `admin_validated_by` berbeda dari `validated_by`. User yang kebetulan memiliki kombinasi role/permission dapat melakukan kedua tahap.

Hal yang sama perlu diperiksa pada:

- review dan approval pinjaman;
- posting dan void opening balance;
- pencatatan dan approval pembayaran;
- void POS;
- proses koreksi ledger.

**Recommended fix**

- Tambahkan invariant pada service/domain layer, bukan hanya UI.
- Tolak approval bila actor sama dengan maker/verifier sebelumnya.
- Simpan alasan override bila organisasi mengizinkan emergency override.
- Override hanya untuk role khusus, wajib audit log dan optional second-factor confirmation.

Contoh:

```php
abort_if(
    (string) $member->admin_validated_by === (string) $approver->id,
    422,
    'Verifier administrasi tidak boleh menjadi approver final.'
);
```

---

### ADMIN-05 — High: Kredensial System Admin Default Tertanam di Seeder

`RolePermissionSeeder` membuat:

```text
email: admin@erp.com
password: password
role: System Admin
```

**Impact**

Bila seeder dijalankan pada production/staging yang dapat diakses, tersedia akun superadmin dengan password yang diketahui dari source code.

**Recommended fix**

- Pisahkan permission seeding dari bootstrap user.
- Jangan membuat admin default pada production.
- Untuk local/demo, gunakan seeder khusus environment dan password acak.
- Bootstrap production melalui command interaktif atau secret environment sekali pakai.
- Paksa reset password dan aktifkan 2FA sebelum akses pertama.
- Tambahkan deployment check yang gagal bila akun default ditemukan.

---

### ADMIN-06 — Medium: Permission Drift dan Hard-Coded Role Name

Terdapat dua permission yang mirip:

```text
validate_cooperative_member
verify_cooperative_member
```

Request verifikasi menerima salah satu dari keduanya. Approval final juga memeriksa role string `System Admin` atau `Pengurus Koperasi` sekaligus permission.

**Risk**

- sulit mengetahui permission mana yang canonical;
- perubahan nama role dapat merusak authorization;
- user dengan permission yang benar tetapi role custom dapat ditolak;
- role dan permission dapat saling bertentangan.

**Recommended fix**

- Pilih satu permission canonical, misalnya `verify_cooperative_member`.
- Migrasikan assignment lama lalu hapus permission alias setelah masa transisi.
- Policy/FormRequest sebaiknya berbasis permission, bukan nama role, kecuali role memang invariant domain yang disengaja.
- Gunakan enum permission pada seluruh kode, tidak memakai string literal tersebar.
- Tambahkan test yang memastikan semua enum permission dibuat oleh seeder dan tidak ada permission orphan.

---

### ADMIN-07 — Medium: Route Middleware Administratif Terlalu Lebar

Sebagian route administrasi anggota dikelompokkan di bawah `can:view_cooperative_member`, termasuk resource member dan action activate/deactivate/resign. Controller memang memanggil policy yang lebih ketat, tetapi struktur route membuat intent authorization kurang jelas dan rawan regresi.

**Recommended fix**

Pisahkan route berdasarkan capability:

```php
Route::middleware('can:view_cooperative_member')->group(function () {
    Route::get('members', ...);
    Route::get('members/{member}', ...);
});

Route::middleware('can:manage_cooperative_member')->group(function () {
    Route::post('members', ...);
    Route::put('members/{member}', ...);
    Route::post('members/{member}/activate', ...);
});

Route::get('members/export', ...)
    ->middleware('can:export_cooperative_member');
```

Policy tetap wajib dipertahankan untuk object-level authorization.

---

### ADMIN-08 — Medium: Raw Model Serialization Memperbesar Eksposur PII

Beberapa controller Inertia dan API mengirim model anggota beserta relation secara langsung. Model menyimpan identitas, NPWP, rekening, tanggal lahir, alamat, dan data finansial.

**Recommended fix**

- Gunakan DTO/API Resource khusus untuk list, detail operasional, dan export.
- List anggota tidak perlu mengirim nomor rekening penuh atau NPWP penuh.
- Pisahkan endpoint “sensitive detail” dengan permission dan audit khusus.
- Masking dilakukan server-side, bukan hanya pada komponen Vue.
- Review seluruh `load()` dan relation nested agar tidak mengirim data berlebih.

---

### ADMIN-09 — Medium: Hard Delete Anggota Finansial Masih Tersedia

`CooperativeMemberController::destroy()` melakukan soft delete karena model memakai `SoftDeletes`, tetapi action ini tetap berisiko pada domain finansial. Penghapusan anggota dapat mengubah hasil query, report, dan referensi historis bila relasi/global scope tidak diperhitungkan.

**Recommended fix**

- Untuk anggota yang memiliki invoice, payment, ledger, loan, POS transaction, atau SHU allocation, gunakan archive/deactivate, bukan delete.
- Policy `delete` harus menjalankan guard domain.
- Record historis tetap terlihat pada laporan dengan label archived/resigned.
- Restore hanya boleh melalui proses administratif dan audit.

---

### ADMIN-10 — Medium: Legacy Opening Balance Membuka Dua Jalur Pencatatan

API anggota admin masih mempunyai fallback yang menulis opening balance lewat service legacy ketika user tidak memiliki permission wizard dan anggota belum memiliki batch aktif.

**Risk**

- dua sumber kebenaran untuk saldo awal;
- hasil berbeda antara web wizard dan API legacy;
- audit dan kategorisasi POKOK/WAJIB dapat tidak konsisten;
- permission wizard dapat dilewati melalui jalur fallback oleh role custom.

**Recommended fix**

- Jadikan wizard/batch opening balance satu-satunya jalur write.
- API create/update member hanya mengembalikan warning atau membuat draft batch, tidak langsung menulis legacy ledger.
- Deprecate field `opening_saving_balance` setelah client bermigrasi.
- Tambahkan migration/command untuk mendeteksi entry legacy yang belum memiliki batch.

---

### ADMIN-11 — Medium: Statistik, Lookup, dan Batch Harus Mengikuti Authorization Scope

Beberapa controller membatasi query utama tetapi tidak membatasi data pendukung seperti:

- statistik global;
- pilihan anggota pada dropdown;
- daftar loan types atau organization;
- status counts;
- batch invoice IDs dari request.

**Recommended fix**

- Buat satu scoped base query dan clone query tersebut untuk list, statistik, dan export.
- Setiap batch operation harus memastikan seluruh ID berada dalam scope user.
- Bila jumlah record yang ditemukan tidak sama dengan jumlah ID request, tolak seluruh batch daripada memproses sebagian secara diam-diam.

---

### ADMIN-12 — Medium: Audit dan Revocation Harus Menjadi Bagian dari State Transition

Audit log sudah tersedia pada banyak flow, tetapi belum seluruh transition sensitif menjamin:

- event audit sukses;
- token/sesi yang tidak lagi valid dicabut;
- notification dikirim setelah commit;
- actor, reason, old state, new state, dan correlation ID tercatat.

**Recommended fix**

Buat state transition service yang konsisten untuk:

- member lifecycle;
- loan lifecycle;
- payment approval/void;
- opening balance post/void;
- POS void/return;
- ledger revision/cancellation.

Setiap transition harus atomic, idempotent, dan mempunyai audit assertion pada test.

## Hal yang Sudah Baik

- Role koperasi telah dibedakan menjadi admin, manajer, pengurus, dan kasir.
- Approval pinjaman menggunakan policy per aksi.
- Validasi anggota menggunakan database transaction.
- Notification pada validasi anggota dikirim melalui `DB::afterCommit()`.
- Opening balance sudah memiliki permission berbeda untuk manage, approve, dan void.
- Banyak write endpoint memakai throttle dan idempotency middleware.
- Ownership check tersedia pada sejumlah endpoint member.

## Target Arsitektur Admin Koperasi

1. Role menentukan permission bisnis, permission diterjemahkan menjadi ability domain yang sempit.
2. Route melakukan coarse-grained authorization, policy melakukan object-level authorization, service menjaga invariant bisnis.
3. Semua query administratif mengikuti organization scope yang sama.
4. Maker-checker ditegakkan pada domain service.
5. PII dikirim minimal dan dimasking secara default.
6. Perubahan role/status mencabut token yang tidak sesuai.
7. Tidak ada kredensial default atau wildcard mobile token.
8. Test matrix memverifikasi positive dan negative access untuk setiap role.