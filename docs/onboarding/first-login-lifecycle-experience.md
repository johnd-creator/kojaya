# ONB-08 — First Login & Member Lifecycle Experience

## Ringkasan Eksekutif

Dokumen ini mendokumentasikan implementasi fitur **ONB-08: First Login & Member Lifecycle Experience** pada platform Kojaya (KojayaPro & Kojayaku). Fitur ini menyediakan arsitektur pengalaman siklus hidup anggota (*member lifecycle experience*) yang aman, deterministik, dan bersumber tunggal (*single source of truth*), memastikan anggota yang baru pertama kali login melalui Google SSO maupun autentikasi berbasis sandi (Fortify) diarahkan dan dibatasi hak aksesnya secara ketat sesuai status validasi keanggotaannya.

Implementasi ini menjaga otoritas penuh alur verifikasi admin dan persetujuan pengurus (**ONB-03**) tanpa adanya celah eskalasi mandiri (*self-promotion*) oleh anggota.

---

## 1. Pemetaan Pengalaman Siklus Hidup (Single Source of Truth)

Seluruh logika penurunan status diturunkan secara kanonikal melalui enum `App\Enums\Cooperative\MemberLifecycleExperience`. Enum ini memetakan pasangan status basis data `status` dan `validation_status` dari model `CooperativeMember` menjadi 6 pengalaman terdefinisi:

| Status Kanonikal (`status`) | Status Validasi (`validation_status`) | Lifecycle Experience Enum | Kode String | Label Bahasa Indonesia | Target Routing Awal | Hak Akses Finansial |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `PENDING` | `PENDING` | `WaitingVerification` | `WAITING_VERIFICATION` | Menunggu Verifikasi Admin | `/member/onboarding` | ❌ Tidak |
| `PENDING` | `PENDING_VALIDATION` / `PENDING_REVIEW` | `UnderReview` | `UNDER_REVIEW` | Menunggu Approval Pengurus | `/member/onboarding` | ❌ Tidak (Pratinjau) |
| `INACTIVE` | `REVISION` | `RevisionRequired` | `REVISION_REQUIRED` | Perlu Revisi | `/member/onboarding` | ❌ Tidak |
| `INACTIVE` | `REJECTED` | `Rejected` | `REJECTED` | Ditolak | `/member/onboarding` (Read-only) | ❌ Tidak |
| `ACTIVE` | `ACTIVE` | `Active` | `ACTIVE` | Aktif | `/member` (Dashboard) | ✅ Penuh |
| `INACTIVE` | `INACTIVE` | `BlockedUnknown` | `BLOCKED_UNKNOWN` | Akses Dibatasi | HTTP 403 Forbidden | ❌ Tidak |
| `RESIGNED` | *Nilai apapun* | `BlockedUnknown` | `BLOCKED_UNKNOWN` | Akses Dibatasi | HTTP 403 Forbidden | ❌ Tidak |
| *Nilai lainnya / kontradiktif* | *Nilai lainnya / null* | `BlockedUnknown` | `BLOCKED_UNKNOWN` | Akses Dibatasi | HTTP 403 Forbidden | ❌ Tidak |

### Karakteristik Keamanan Fail-Closed & Proyeksi Deterministik (R1-02)
- **Penurunan Murni Status**: Seluruh pengalaman siklus hidup diturunkan **hanya** dari `status` dan `validation_status`, tidak pernah bergantung pada `onboarding_submitted_at`.
- **Proyeksi `review_state`**: Metode `reviewState()` pada `MemberLifecycleExperience` merupakan proyeksi deterministik:
  - `WaitingVerification` -> `'pending'`
  - `UnderReview` -> `'review'`
  - `RevisionRequired` -> `'revision'`
  - `Rejected` -> `'rejected'`
  - `Active` -> `'approved'`
  - `BlockedUnknown` -> `'blocked'`
- Kombinasi status yang tidak sah atau kontradiktif (misalnya `ACTIVE` dengan `PENDING`, `INACTIVE` dengan `INACTIVE`, `RESIGNED`, atau nilai korup/hilang) secara otomatis jatuh ke `BLOCKED_UNKNOWN` (HTTP 403 Forbidden fail-closed).

---

## 2. Arsitektur First-Login & Dashboard Routing

### A. Autentikasi Web Berbasis Sandi (Fortify LoginResponse)
- Terletak di `App\Http\Responses\LoginResponse`.
- Saat pengguna dengan peran `Anggota` berhasil login:
  - Jika `isActive()`: diarahkan ke dasbor anggota (`route('member.dashboard')`) atau intended URL.
  - Jika `isNonActiveLifecycle()` (`WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`, `REJECTED`):
    - **Keamanan `url.intended`**: Memanggil `session()->forget('url.intended')` sebelum redirect agar anggota non-aktif tidak dapat dibelokkan ke rute finansial/POS.
    - Diarahkan ke halaman status siklus hidup onboarding (`route('member.onboarding')`).
  - Jika `isBlocked()`: dibatalkan dengan status **HTTP 403 Forbidden**.

### B. Autentikasi Google SSO (GoogleSsoController)
- Terletak di `App\Http\Controllers\Auth\GoogleSsoController::redirectDestination()`.
- Menyelaraskan rute pengembalian pengguna anggota yang masuk melalui alur OAuth Google:
  - Anggota aktif -> `member.dashboard`.
  - Anggota belum aktif -> `member.onboarding`.
  - Pengguna dengan status kontradiktif -> **HTTP 403**.

### C. Gateway Dasbor ERP Utama (DashboardController)
- Terletak di `App\Http\Controllers\DashboardController::index()`.
- Pengguna dengan peran anggota yang mengakses rute universal `/dashboard` diarahkan sesuai siklus hidupnya:
  - Aktif -> dasbor Kojayaku (`/member`).
  - Non-aktif -> halaman onboarding (`/member/onboarding`).
  - Blokir / Kontradiktif -> HTTP 403.

---

## 3. Gerbang Middleware (Middleware Gates)

Dua lapis pengamanan middleware memastikan fitur-fitur sensitif koperasi terlindungi:

### 1. Web Middleware: `EnsureMemberFullyActive` (`member.active`)
- Memproteksi rute web finansial dan transaksional anggota (misal: simpanan, pinjaman, rekening toko).
- Memeriksa apakah `MemberLifecycleExperience::fromMember($member)->isActive()`.
- Jika anggota berada dalam siklus non-aktif (`WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`, `REJECTED`):
  - Diberikan pengalihan aman ke `/member/onboarding` dengan pesan peringatan sesi terenkripsi: *"Layanan finansial hanya dapat diakses setelah keanggotaan Anda aktif sepenuhnya."*
- Jika status keanggotaan adalah `BLOCKED_UNKNOWN`:
  - Ditolak dengan HTTP 403 Forbidden.
- Setiap penolakan akses dicatat ke dalam `AuditLog` dengan aksi `sso.member.gated_access_denied`.

### 2. API Middleware: `EnsureApiMemberIsActive` (`member.api.active`)
- Memproteksi endpoint API finansial Kojayaku Mobile (misal: pengajuan pinjaman, pembayaran tagihan, penarikan simpanan).
- Memeriksa apakah `MemberLifecycleExperience::fromMember($member)->isActive()`.
- Jika anggota tidak aktif:
  - Mengembalikan respon terstruktur **HTTP 403 Forbidden**:
    ```json
    {
      "message": "Fitur ini hanya dapat diakses oleh anggota koperasi yang berstatus aktif.",
      "error_code": "MEMBER_NOT_ACTIVE"
    }
    ```
- Jika status tidak konsisten:
  - Mengembalikan respon HTTP 403 Forbidden dengan kode kesalahan `MEMBER_BLOCKED_OR_UNKNOWN`.

---

## 4. Proteksi PII & Penutupan Mutasi Sensitif (R1-01, R1-03, R1-04)

### A. Penutupan Mutasi PII Sensitif (R1-01)
`MemberOnboardingSubmitService::submit()` menerapkan *safe profile allowlist* yang sangat ketat:
- **Hanya kolom profil aman yang diizinkan diperbarui**: `name`, `phone`, `address`, `jenis_kelamin`, `tanggal_lahir`, `tempat_lahir`, `pekerjaan`.
- **Kolom sensitif/otoritas diabaikan dan tidak pernah dimutasi**: `identity_number` (NIK), `npwp`, `kategori`, `no_rekening`, `nama_bank`, `nama_pemilik_rekening`, `organization_id`, `employee_id`, `member_no`, `jenis_anggota`, serta `users.email`.
- `status` dan `validation_status` **TIDAK PERNAH** dimutasi oleh pengiriman onboarding anggota, mempertahankan otoritas penuh **ONB-03**.

### B. Anggota Ditolak (R1-03)
- Anggota berstatus `INACTIVE/REJECTED` diarahkan ke `GET /member/onboarding` dalam **mode baca-saja (*read-only*)**.
- Menampilkan pesan penolakan dan `validation_notes` dari admin/pengurus.
- Tidak memiliki tombol kirim ulang (*no resubmit CTA*), tidak ada tombol aktivasi buatan, dan pengiriman `POST /member/onboarding` ditolak dengan **HTTP 403 Forbidden**.

### C. Pemensiunan Wizard Pendaftaran Kedua (R1-04)
- Anggota berstatus `ACTIVE/ACTIVE` yang mengunjungi `GET /member/onboarding` langsung dialihkan ke `member.dashboard`.
- Pengiriman `POST /member/onboarding` oleh anggota aktif ditolak dengan **HTTP 403 Forbidden**.
- Seluruh form wizard pendaftaran kedua (input NIK, NPWP, Bank, dsb.) dipensiunkan dari `Onboarding.vue`.

---

## 5. Paritas Payload API & Mobile

Pada `App\Http\Controllers\Api\AuthController`, endpoint autentikasi mobile (`POST /api/auth/login`, `POST /api/auth/google/mobile`, dan `POST /api/auth/google/sso/token`) menyertakan metadata siklus hidup anggota yang konsisten:

```json
{
  "token_type": "Bearer",
  "token": "...",
  "abilities": ["*"],
  "member_status": "PENDING",
  "validation_status": "PENDING",
  "lifecycle_experience": "WAITING_VERIFICATION",
  "onboarding_next_step": "waiting_admin_acceptance"
}
```

Daftar pemetaan `onboarding_next_step` ke pengalaman siklus hidup:
- `Active` -> `dashboard`
- `UnderReview` -> `waiting_final_approval`
- `RevisionRequired` -> `revision_required`
- `Rejected` -> `rejected`
- `WaitingVerification` -> `waiting_admin_acceptance`
- `BlockedUnknown` -> `blocked` (HTTP 403 fail-closed)

---

## 6. Antarmuka Pengguna (Kojayaku Onboarding UI)

Komponen frontend `resources/js/pages/Kojayaku/Onboarding.vue` telah disempurnakan menjadi *Lifecycle Status UX*:
- Menghilangkan form wizard pendaftaran kedua (NIK, NPWP, data rekening bank).
- Menghilangkan tombol persetujuan mandiri (*self-approval CTA*) atau tombol aktivasi buatan.
- Menampilkan status siklus hidup dalam Bahasa Indonesia yang informatif:
  - **Menunggu Verifikasi Admin**: Menjelaskan berkas sedang dalam antrean verifikasi petugas koperasi, dengan opsi memperbarui data profil dasar (telepon, alamat, tanggal lahir).
  - **Menunggu Persetujuan Pengurus**: Mode pratinjau yang menjelaskan data telah diverifikasi petugas dan menunggu persetujuan akhir pengurus.
  - **Perlu Revisi**: Menampilkan catatan perbaikan yang dibutuhkan dan mengizinkan pembaruan data profil yang aman.
  - **Ditolak**: Kartu status penolakan baca-saja (*read-only*) lengkap dengan alasan/catatan verifikasi, tanpa tombol kirim ulang.
  - **Aktif**: Konfirmasi keanggotaan penuh dan tautan langsung ke dasbor.

---

## 7. Verifikasi & Pengujian

Suite pengujian komprehensif di `tests/Feature/Onboarding/MemberFirstLoginLifecycleExperienceTest.php` mencakup 10 skenario:

1. `test_derived_lifecycle_experience_mapping_resolves_all_canonical_and_inconsistent_states`:
   - Validasi pemetaan kanonikal 5 status valid.
   - Validasi fail-closed pasangan kontradiktif (termasuk `INACTIVE/INACTIVE` dan `RESIGNED/RESIGNED`).
   - Validasi proyeksi deterministik `reviewState()`.
   - Validasi invariant R1-02 (`onboarding_submitted_at` tidak mengubah status pengalaman).
2. `test_web_fortify_login_redirects_non_active_members_to_onboarding_and_active_to_dashboard`:
   - Pengujian login sandi web untuk semua status non-aktif mengarah ke onboarding.
   - Pengujian keamanan `url.intended` (dihapus saat redirect anggota non-aktif).
   - Anggota aktif mengarah ke dasbor.
   - Anggota dengan status kontradiktif gagal dengan HTTP 403.
3. `test_dashboard_controller_routes_members_appropriately`:
   - Routing gateway `/dashboard` mengarah sesuai siklus hidup.
4. `test_google_sso_redirect_destination_routes_members_by_lifecycle`:
   - Pengujian redirect destination Google SSO sesuai siklus hidup.
5. `test_web_member_active_middleware_gate_denies_non_active_and_permits_active`:
   - Pengujian pemblokiran rute web finansial untuk anggota belum aktif dan penolakan 403 untuk status kontradiktif.
6. `test_api_member_active_middleware_gate_denies_non_active_and_permits_active`:
   - Pengujian proteksi endpoint API finansial dengan error code `MEMBER_NOT_ACTIVE` (403).
7. `test_api_auth_login_returns_lifecycle_experience_and_onboarding_next_step`:
   - Pengujian integritas payload API login untuk seluruh status siklus hidup serta 403 fail-closed untuk status inkonsisten.
8. `test_legacy_onboarding_submit_never_self_advances_status_preserving_onb03_authority`:
   - Memastikan `POST /member/onboarding` memperbarui profil aman namun tidak memutasi `validation_status` maupun `status` (preservasi ONB-03).
   - Memastikan kolom PII sensitif (NIK, email, NPWP, kategori, bank) diabaikan dan tidak termutasi (R1-01).
   - Memastikan anggota aktif ditolak 403 pada `POST /member/onboarding` (R1-04).
   - Memastikan anggota ditolak ditolak 403 pada `POST /member/onboarding` (R1-03).
9. `test_get_member_onboarding_renders_or_redirects_by_lifecycle`:
   - Anggota aktif dialihkan ke dasbor (R1-04).
   - Anggota ditolak menerima halaman baca-saja 200 OK dengan catatan validasi (R1-03).
   - Anggota menunggu/revisi menerima halaman dengan kemampuan edit profil aman.
   - Anggota berstatus tidak konsisten menerima 403 Forbidden.
10. `test_multi_tenant_isolation_member_experience_strictly_scoped_to_organization`:
    - Memastikan isolasi data multi-tenant dan boundary koperasi tetap terjaga.
