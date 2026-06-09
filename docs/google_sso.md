# Rencana Pengembangan Google SSO dan Onboarding Anggota

## 1. Ringkasan

Dokumen ini menjelaskan rencana pengembangan Google Single Sign-On (SSO) untuk KojayaPro dan Kojayaku, terutama untuk pengalaman login anggota koperasi. Target akhirnya adalah:

- User dapat melihat tombol "Masuk dengan Google" di halaman login.
- Anggota yang Gmail-nya sudah terdaftar dapat langsung login ke akun anggota.
- Calon anggota atau anggota baru yang login dengan Google diarahkan ke onboarding anggota.
- Admin Koperasi memvalidasi data anggota baru sebelum status anggota aktif penuh.
- Halaman profile dan settings memastikan data anggota lengkap, konsisten, dan siap dipakai untuk simpanan, pinjaman, SHU, poin, dan notifikasi.

Fitur ini harus tetap mengikuti arsitektur aplikasi saat ini: Laravel 12, Fortify, Inertia.js v2, Vue 3, Spatie Permission, dan model `User` yang dapat terhubung ke `CooperativeMember`.

## 2. Tujuan Bisnis

1. Mempercepat akses anggota ke Kojayaku tanpa harus mengingat password baru.
2. Mengurangi beban Admin Koperasi dalam membuat akun login manual untuk anggota.
3. Menjaga proses validasi koperasi tetap terkendali, karena tidak semua orang yang berhasil login Google otomatis menjadi anggota aktif.
4. Menjamin data profile anggota lengkap sebelum anggota memakai fitur sensitif seperti pinjaman, penarikan simpanan, klaim SHU, atau penukaran reward.
5. Membuat alur login anggota yang lebih familiar, aman, dan mudah digunakan di mobile.

## 3. Scope

### In Scope

- Google OAuth login untuk web app.
- Tombol "Masuk dengan Google" di halaman login.
- Callback Google OAuth di backend.
- Penyimpanan identitas provider Google.
- Linking Google account ke `users.email` dan `cooperative_members.email`.
- Onboarding page untuk anggota baru.
- Status validasi Admin Koperasi untuk anggota hasil Google SSO.
- Profile completeness untuk anggota.
- Update UI settings/profile agar anggota dapat melengkapi data.
- Audit log untuk event penting.
- Test feature dan test authorization.

### Out of Scope untuk fase awal

- Login Google untuk native mobile app.
- Multi-provider SSO selain Google.
- SSO enterprise Google Workspace domain restriction.
- Auto-approval anggota tanpa validasi admin.
- Import anggota massal dari Google Contacts.

## 4. Kondisi Sistem Saat Ini

### Authentication

- Aplikasi memakai Laravel Fortify untuk login/register.
- Login page berada di `resources/js/pages/auth/Login.vue`.
- Setelah login, redirect dikendalikan oleh response class seperti `LoginResponse`.
- User mempunyai role dari Spatie Permission.

### Member Portal

- Route member berada di prefix `/member`.
- Onboarding anggota sudah ada di:
  - `GET /member/onboarding`
  - `POST /member/onboarding/steps`
- Profile anggota sudah ada di:
  - `GET /member/profile`
  - `PUT /member/profile`
- Komponen onboarding berada di `resources/js/components/Kojayaku/OnboardingChecklist.vue`.
- Page onboarding berada di `resources/js/pages/Kojayaku/Onboarding.vue`.
- Page profile anggota berada di `resources/js/pages/Kojayaku/Profile.vue`.

### Data Anggota

Model utama:

- `User`
- `CooperativeMember`
- `MemberOnboardingProgress`

Kolom anggota yang sudah relevan:

- `user_id`
- `member_no`
- `name`
- `email`
- `phone`
- `address`
- `joined_at`
- `status`
- field tambahan anggota seperti `no_anggota`, `nama_anggota`, `no_telp`, `npwp`, `jenis_anggota`, `kategori`, dan tanggal aktif, sesuai migrasi yang sudah ada.

## 5. Prinsip Desain

1. Google SSO hanya membuktikan kepemilikan email, bukan otomatis membuktikan keanggotaan koperasi.
2. Jika email Google cocok dengan anggota existing, sistem boleh melakukan linking otomatis dengan kontrol yang jelas.
3. Jika email belum dikenal, sistem membuat user dan draft anggota, lalu mengarahkan user ke onboarding.
4. Admin Koperasi menjadi validator final untuk aktivasi anggota baru.
5. Data anggota harus lengkap sebelum akses fitur finansial sensitif.
6. Semua perubahan identitas, linking, validasi, dan penolakan harus tercatat di audit log.
7. Akses tetap memakai role dan permission yang sudah ada.

## 6. User Flow Target

### Flow A: Anggota Existing Login dengan Gmail Terdaftar

1. Anggota membuka halaman login.
2. Anggota klik "Masuk dengan Google".
3. Google mengembalikan email terverifikasi.
4. Sistem mencari `users.email`.
5. Jika user ditemukan:
   - Login user tersebut.
   - Jika user punya role `Anggota`, redirect ke `/member`.
   - Jika profile belum lengkap, redirect ke `/member/onboarding`.
6. Jika `users.email` belum ada, sistem mencari `cooperative_members.email`.
7. Jika anggota ditemukan:
   - Buat user baru atau link ke user kosong.
   - Assign role `Anggota`.
   - Set `cooperative_members.user_id`.
   - Login user.
   - Redirect ke `/member/onboarding` jika data belum lengkap.

### Flow B: Email Google Belum Terdaftar

1. User klik "Masuk dengan Google".
2. Sistem menerima email Google yang sudah verified.
3. Sistem tidak menemukan user atau anggota existing.
4. Sistem membuat user dengan role `Anggota`.
5. Sistem membuat record `CooperativeMember` status `PENDING` atau `PENDING_VALIDATION`.
6. User diarahkan ke onboarding untuk melengkapi data wajib.
7. Setelah submit onboarding, status tetap menunggu validasi admin.
8. Admin Koperasi meninjau dan memvalidasi.
9. Jika valid:
   - Status anggota menjadi `ACTIVE`.
   - Nomor anggota dibuat jika belum ada.
   - Onboarding dianggap selesai.
10. Jika ditolak:
   - Status menjadi `REJECTED` atau tetap `PENDING` dengan catatan revisi.
   - Anggota melihat alasan dan dapat memperbaiki data.

### Flow C: User Google Sudah Ada tetapi Bukan Anggota

1. User login Google.
2. Sistem menemukan `users.email`.
3. User tidak punya `cooperativeMember`.
4. Sistem menampilkan pilihan:
   - Ajukan sebagai anggota koperasi.
   - Masuk ke dashboard sesuai role existing.
5. Jika ajukan anggota, user diarahkan ke onboarding anggota baru.

### Flow D: Email Sama tetapi Sudah Dipakai User Lain

1. Google callback membawa email.
2. Sistem menemukan user dengan email yang sama.
3. Jika `google_id` belum terisi:
   - Link Google provider ke user tersebut.
   - Wajib pastikan `email_verified` dari Google bernilai true.
4. Jika `google_id` berbeda:
   - Tolak login.
   - Catat security event.
   - Tampilkan pesan untuk hubungi admin.

## 7. Data Model yang Dibutuhkan

### Opsi A: Kolom Provider di `users`

Tambahkan kolom:

- `google_id` nullable string unique
- `google_avatar` nullable string
- `google_linked_at` nullable timestamp
- `last_google_login_at` nullable timestamp

Kelebihan:

- Cepat dan sederhana.
- Cukup untuk satu provider.

Kekurangan:

- Kurang fleksibel jika nanti ada provider lain.

### Opsi B: Tabel `social_accounts`

Buat tabel baru:

- `id`
- `user_id`
- `provider` seperti `google`
- `provider_id`
- `provider_email`
- `provider_avatar`
- `access_token` encrypted nullable
- `refresh_token` encrypted nullable
- `linked_at`
- `last_login_at`
- timestamps

Unique index:

- unique `provider`, `provider_id`
- index `provider_email`

Rekomendasi: gunakan Opsi B karena lebih rapi dan future-proof.

### Perubahan Data Anggota

Tambahkan field validasi jika belum tersedia:

- `validation_status` atau gunakan `status`
- `validated_at`
- `validated_by`
- `validation_notes`
- `profile_completed_at`

Jika memakai `status`, nilai yang disarankan:

- `PENDING`
- `PENDING_VALIDATION`
- `ACTIVE`
- `INACTIVE`
- `REJECTED`
- `RESIGNED`

## 8. Validasi Kelengkapan Profile Anggota

Profile anggota dinyatakan lengkap jika minimal memiliki:

- Nama lengkap
- Email terverifikasi
- Nomor HP
- Alamat domisili
- Nomor anggota jika sudah divalidasi
- Tanggal bergabung atau tanggal aktif
- Jenis anggota
- Kategori anggota
- Nomor identitas seperti NIK/KTP jika disepakati untuk compliance
- NPWP jika dibutuhkan
- Kontak darurat jika akan dipakai untuk pinjaman
- Persetujuan terms/privacy

Field yang disarankan untuk onboarding lanjutan:

- Tempat lahir
- Tanggal lahir
- Jenis kelamin
- Pekerjaan
- Nama perusahaan/unit
- Nomor rekening bank
- Nama bank
- Nama pemilik rekening
- Upload KTP
- Upload KK atau dokumen pendukung

Catatan: field sensitif seperti NIK, dokumen KTP, dan rekening harus memiliki kebijakan akses dan audit yang jelas.

## 9. Redirect Rules Setelah Login

Prioritas redirect:

1. Jika user harus two-factor challenge, ikuti Fortify flow.
2. Jika user punya role `Anggota` dan punya member status `PENDING` atau `PENDING_VALIDATION`, redirect ke `/member/onboarding`.
3. Jika user punya role `Anggota` dan profile belum lengkap, redirect ke `/member/onboarding`.
4. Jika user punya role `Anggota` dan status `ACTIVE`, redirect ke `/member`.
5. Jika user punya role admin, redirect ke dashboard admin sesuai role.
6. Jika user tidak punya role jelas, redirect ke halaman pemilihan role atau halaman support.

## 10. UI/UX yang Dibutuhkan

### Halaman Login

Tambahkan tombol:

- Label: `Masuk dengan Google`
- Lokasi: di bawah form email/password atau di atas separator `atau`
- Icon: gunakan icon Google jika asset tersedia, atau lucide `Chrome` sementara.
- Behavior: redirect ke route `auth.google.redirect`.

State UI:

- Loading saat redirect.
- Error jika Google callback gagal.
- Error jika email Google belum verified.
- Error jika akun diblokir atau ditolak.

### Onboarding Anggota Baru

Halaman onboarding perlu memiliki step:

1. Verifikasi email Google
2. Lengkapi data pribadi
3. Lengkapi kontak dan alamat
4. Lengkapi data keanggotaan
5. Upload dokumen
6. Review dan submit validasi
7. Menunggu validasi Admin Koperasi

Tampilan untuk status:

- `Belum lengkap`
- `Menunggu validasi`
- `Perlu revisi`
- `Disetujui`
- `Ditolak`

### Profile Anggota

Profile anggota harus menampilkan:

- Informasi login
  - Email
  - Status verifikasi email
  - Provider login Google terhubung atau belum
- Informasi anggota
  - Nomor anggota
  - Status anggota
  - Tanggal aktif
  - Jenis dan kategori anggota
- Informasi pribadi
- Informasi kontak
- Dokumen
- Progress kelengkapan profile

### Settings

Tambahkan section:

- `Akun Login`
  - Email
  - Google account linked
  - Tombol link/unlink Google jika aman
- `Keamanan`
  - Password fallback
  - Two-factor authentication
- `Kelengkapan Data`
  - Progress profile completeness
  - Link ke `/member/profile` atau `/member/onboarding`

## 11. Admin Koperasi Validation Flow

### Menu Admin

Tambahkan filter di page anggota:

- `Menunggu validasi`
- `Perlu revisi`
- `Disetujui`
- `Ditolak`

Admin Koperasi perlu dapat:

- Melihat daftar anggota hasil Google SSO yang pending.
- Membuka detail data onboarding.
- Melihat dokumen upload.
- Approve anggota.
- Reject anggota dengan alasan.
- Request revision dengan catatan.
- Generate nomor anggota jika belum ada.
- Link anggota ke user existing jika terdeteksi duplikat.

### Approval Rules

Admin dapat approve jika:

- Email verified.
- Data wajib lengkap.
- Tidak ada duplikasi nomor identitas.
- Tidak ada duplikasi email aktif.
- Dokumen valid.

Setelah approve:

- `CooperativeMember.status = ACTIVE`
- `validated_at` terisi
- `validated_by` terisi
- role `Anggota` dipastikan ada di user
- onboarding complete
- optional: generate invoice simpanan pokok/wajib awal

## 12. Backend Components

### Package

Evaluasi package:

- `laravel/socialite`

Jika dependency belum ada, tambahkan via Composer setelah approval.

### Routes

Web routes:

```php
Route::get('/auth/google/redirect', [GoogleSsoController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleSsoController::class, 'callback'])
    ->middleware('guest')
    ->name('auth.google.callback');
```

Admin/member routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/member/onboarding', ...);
    Route::put('/member/profile', ...);
});

Route::middleware(['auth', 'can:manage_cooperative_member'])->group(function () {
    Route::post('/cooperative/members/{member}/validate', ...);
    Route::post('/cooperative/members/{member}/request-revision', ...);
    Route::post('/cooperative/members/{member}/reject', ...);
});
```

### Controllers

Tambahkan:

- `GoogleSsoController`
- `MemberOnboardingController` jika ingin memisahkan dari `MemberPortalController`
- action validasi di `CooperativeMemberController` atau controller khusus `CooperativeMemberValidationController`

### Services

Tambahkan service:

- `GoogleSsoService`
- `MemberAccountLinkingService`
- `MemberProfileCompletenessService`
- `MemberValidationService`

Service responsibilities:

- Resolve Google user.
- Match ke existing `User`.
- Match ke existing `CooperativeMember`.
- Create pending member.
- Link social account.
- Calculate profile completeness.
- Determine redirect destination.
- Audit log.

### Form Requests

Tambahkan:

- `CompleteMemberOnboardingRequest`
- `UpdateMemberProfileRequest` yang diperluas
- `ValidateCooperativeMemberRequest`
- `RejectCooperativeMemberRequest`
- `RequestMemberRevisionRequest`

## 13. Security Requirements

1. Hanya terima Google user jika `email_verified = true`.
2. Jangan menyimpan access token Google jika tidak diperlukan.
3. Jika token disimpan, harus encrypted.
4. Gunakan `state` OAuth untuk CSRF protection.
5. Batasi callback domain sesuai environment.
6. Audit event:
   - Google login success
   - Google login failed
   - Social account linked
   - Social account conflict
   - Member validation approved
   - Member validation rejected
   - Profile data changed
7. Jangan expose Google provider ID ke frontend.
8. Email match harus exact dan normalized lowercase.
9. Jika ada konflik email/provider, jangan auto-merge tanpa admin.
10. Rate limit route redirect/callback jika memungkinkan.

## 14. Environment Variables

Tambahkan ke `.env.example`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
GOOGLE_SSO_ENABLED=false
GOOGLE_SSO_AUTO_LINK_EXISTING_MEMBERS=true
GOOGLE_SSO_ALLOW_NEW_MEMBER_REGISTRATION=true
```

Tambahkan config:

- `config/services.php`
- optional `config/auth-sso.php`

## 15. Phased Development Plan

### Phase 0: Discovery dan Finalisasi Aturan

Target: 0.5 sampai 1 hari.

Deliverables:

- Finalisasi apakah user baru boleh daftar sebagai calon anggota lewat Google.
- Finalisasi field wajib profile.
- Finalisasi status anggota baru.
- Finalisasi apakah nomor anggota dibuat saat onboarding atau saat approval.
- Finalisasi apakah password fallback wajib.

Acceptance criteria:

- Checklist business rule disetujui.
- Tidak ada ambiguity pada matching email.

### Phase 1: Data Model dan Config Google SSO

Target: 1 sampai 2 hari.

Tasks:

- Install dan konfigurasi Socialite.
- Tambah tabel `social_accounts`.
- Tambah field validasi anggota jika belum ada.
- Tambah config Google di `config/services.php`.
- Tambah `.env.example`.
- Tambah factory/test data untuk social account.

Acceptance criteria:

- Migration berhasil.
- Config terbaca dari environment.
- Test model relationship pass.

### Phase 2: Backend Google OAuth

Target: 2 sampai 3 hari.

Tasks:

- Buat `GoogleSsoController`.
- Buat `GoogleSsoService`.
- Implement redirect ke Google.
- Implement callback Google.
- Implement social account linking.
- Implement exact email matching:
  - `users.email`
  - `cooperative_members.email`
- Implement auto-create pending member jika enabled.
- Implement redirect destination.
- Tambah audit log.

Acceptance criteria:

- Existing user dengan Gmail matching bisa login.
- Existing member tanpa user dapat dibuatkan user dan login.
- Email baru diarahkan ke onboarding.
- Email conflict ditolak dengan pesan jelas.
- Test happy path dan failure path pass.

### Phase 3: Login UI

Target: 0.5 sampai 1 hari.

Tasks:

- Tambah tombol `Masuk dengan Google` di `auth/Login.vue`.
- Tambah separator visual dari login password.
- Tambah state error callback.
- Pastikan responsive mobile.
- Feature flag berdasarkan `GOOGLE_SSO_ENABLED`.

Acceptance criteria:

- Tombol muncul jika SSO aktif.
- Tombol tidak muncul jika SSO disabled.
- Klik tombol redirect ke route Google.
- Build frontend berhasil.

### Phase 4: Onboarding Anggota Baru

Target: 3 sampai 5 hari.

Tasks:

- Perluas onboarding page `/member/onboarding`.
- Tambah step data pribadi.
- Tambah step kontak dan alamat.
- Tambah step data keanggotaan.
- Tambah step dokumen.
- Tambah submit untuk validasi admin.
- Simpan progress di `member_onboarding_progress`.
- Tambah `MemberProfileCompletenessService`.

Acceptance criteria:

- Member pending dapat melengkapi data bertahap.
- Progress tersimpan.
- Profile completeness dihitung konsisten.
- User pending tidak bisa mengakses fitur sensitif sebelum validasi.
- User melihat status menunggu validasi setelah submit.

### Phase 5: Admin Validation

Target: 2 sampai 4 hari.

Tasks:

- Tambah filter validasi di page anggota.
- Tambah detail validasi anggota.
- Tambah action approve.
- Tambah action reject.
- Tambah action request revision.
- Tambah audit log.
- Tambah notification opsional untuk anggota.

Acceptance criteria:

- Admin Koperasi dapat melihat anggota pending.
- Admin dapat approve anggota lengkap.
- Admin dapat reject dengan alasan.
- Admin dapat request revision.
- Setelah approve, anggota menjadi aktif dan bisa masuk member dashboard.

### Phase 6: Profile dan Settings Completeness

Target: 2 sampai 3 hari.

Tasks:

- Perluas `/member/profile`.
- Tambah section profile completeness.
- Tambah data login Google linked.
- Tambah field wajib yang belum ada.
- Tambah validasi request.
- Sinkronkan field profile dengan `CooperativeMember`.
- Pastikan settings profile tidak membingungkan antara user profile dan member profile.

Acceptance criteria:

- Anggota melihat data lengkap yang dibutuhkan koperasi.
- Anggota tahu field mana yang belum lengkap.
- Update profile memperbarui completeness.
- Admin dapat melihat data yang sama di page anggota.

### Phase 7: Access Control dan Feature Gating

Target: 1 sampai 2 hari.

Tasks:

- Buat middleware atau service check untuk member status.
- Batasi fitur sensitif untuk status pending.
- Pastikan dashboard tetap memberi arahan onboarding.
- Pastikan admin roles tidak terganggu oleh SSO.

Acceptance criteria:

- Member pending hanya dapat membuka onboarding, profile, dan support.
- Member active dapat membuka fitur member normal.
- Admin login Google jika email sama tetap masuk ke dashboard admin.

### Phase 8: Testing dan Hardening

Target: 2 sampai 4 hari.

Tests:

- Google login existing user.
- Google login existing member tanpa user.
- Google login email baru.
- Google login email unverified.
- Google provider conflict.
- Pending member redirect onboarding.
- Active member redirect dashboard.
- Admin approve member.
- Admin reject member.
- Profile completeness.
- Access gating untuk fitur sensitif.
- Audit log exists.

Acceptance criteria:

- PHPUnit feature tests pass.
- `npm run build` pass.
- Manual QA di browser pass.
- Tidak ada regression login password.

### Phase 9: Rollout Production

Target: 1 sampai 2 hari.

Tasks:

- Buat Google OAuth app di Google Cloud Console.
- Set redirect URI production.
- Isi env production.
- Jalankan migration.
- Clear config cache.
- Enable `GOOGLE_SSO_ENABLED`.
- Monitor login logs.
- Siapkan fallback jika Google SSO error.

Acceptance criteria:

- Google login production berhasil.
- Password login tetap berjalan.
- Admin dapat disable SSO via env jika ada incident.

## 16. Migration Plan

1. Deploy kode dengan `GOOGLE_SSO_ENABLED=false`.
2. Jalankan migration.
3. Deploy UI tombol tetapi hidden oleh flag.
4. Konfigurasi Google credentials di staging.
5. Test staging.
6. Enable staging.
7. Konfigurasi production.
8. Enable production pada jam low traffic.
9. Monitor error login dan onboarding.

## 17. Backward Compatibility

- Login email/password tetap tersedia.
- User existing tidak wajib link Google.
- Admin tetap bisa membuat akun anggota manual.
- Member existing dapat tetap login dengan password lama.
- Jika Google SSO disabled, aplikasi kembali ke login normal.

## 18. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Email Google tidak sama dengan email anggota di database | Anggota gagal auto-link | Sediakan admin linking manual dan instruksi ubah email |
| Duplikasi email di data lama | Salah link akun | Blocking conflict, audit log, validasi admin |
| User baru mengaku sebagai anggota | Risiko data palsu | Status pending, admin validation, dokumen wajib |
| Google OAuth outage | Login anggota terganggu | Password fallback tetap aktif |
| Data profile kurang lengkap | Fitur finansial berisiko | Profile completeness dan gating |
| Token OAuth tersimpan tidak aman | Risiko security | Jangan simpan token jika tidak perlu, encrypt jika disimpan |

## 19. Testing Checklist

### Backend

- [ ] Migration social account.
- [ ] User relationship social accounts.
- [ ] Google callback success.
- [ ] Google callback failed.
- [ ] Existing user match.
- [ ] Existing member match.
- [ ] New pending member creation.
- [ ] Conflict provider ID.
- [ ] Admin approval.
- [ ] Admin rejection.
- [ ] Access gating pending member.

### Frontend

- [ ] Login button visible if enabled.
- [ ] Login button hidden if disabled.
- [ ] Onboarding step navigation.
- [ ] Profile completeness indicator.
- [ ] Profile update validation errors.
- [ ] Admin validation actions.
- [ ] Mobile responsive login.
- [ ] Mobile responsive onboarding.

### Security

- [ ] Only verified Google email accepted.
- [ ] OAuth state validated.
- [ ] Audit logs written.
- [ ] Provider ID not exposed.
- [ ] Pending user cannot access sensitive features.

## 20. Definition of Done

Fitur dianggap selesai jika:

- Google SSO bisa dipakai untuk login anggota existing.
- Anggota baru dari Google diarahkan ke onboarding.
- Admin Koperasi bisa validasi anggota pending.
- Anggota pending tidak bisa memakai fitur finansial sensitif.
- Profile anggota memiliki kelengkapan data yang jelas.
- Password login existing tetap berjalan.
- Tests dan build pass.
- Dokumentasi env dan rollout tersedia.

## 21. Rekomendasi Implementasi Awal

Urutan implementasi yang paling aman:

1. Data model `social_accounts`.
2. Backend Google callback dan linking existing user.
3. Tombol login Google.
4. Redirect onboarding untuk member pending.
5. Onboarding completeness.
6. Admin validation.
7. Feature gating.
8. Rollout production bertahap.

Dengan urutan ini, sistem dapat mengaktifkan Google login untuk user existing lebih dulu, lalu membuka pendaftaran/onboarding anggota baru setelah validasi admin siap.
