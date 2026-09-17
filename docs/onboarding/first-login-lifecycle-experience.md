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
| `INACTIVE` | `REJECTED` | `Rejected` | `REJECTED` | Ditolak | `/member` | ❌ Tidak |
| `ACTIVE` | `ACTIVE` | `Active` | `ACTIVE` | Aktif | `/member` (Dashboard) | ✅ Penuh |
| *Nilai lainnya / kontradiktif* | *Nilai lainnya / null* | `BlockedUnknown` | `BLOCKED_UNKNOWN` | Akses Dibatasi | HTTP 403 Forbidden | ❌ Tidak |

### Karakteristik Keamanan Fail-Closed
Kombinasi status yang tidak sah atau kontradiktif (misalnya `ACTIVE` dengan `PENDING`, `PENDING` dengan `REVISION`, atau nilai yang korup/hilang) secara otomatis jatuh ke `BLOCKED_UNKNOWN`. Sistem langsung menolak permintaan dengan **HTTP 403 Forbidden**, mencatat peringatan keamanan audit, dan tidak membocorkan data koperasi atau anggota.

---

## 2. Arsitektur First-Login & Dashboard Routing

### A. Autentikasi Web Berbasis Sandi (Fortify LoginResponse)
- Terletak di `App\Http\Responses\LoginResponse`.
- Saat pengguna dengan peran `Anggota` berhasil login:
  - Jika `isActive()`: diarahkan langsung ke dasbor anggota (`route('member.dashboard')`).
  - Jika `isNonActiveLifecycle()` (`WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`): diarahkan ke halaman status siklus hidup onboarding (`route('member.onboarding')`).
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
- Jika anggota berada dalam siklus onboarding (`WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`):
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

## 4. Penghapusan Eskalasi Mandiri (Preservasi Otoritas ONB-03)

Sebelum implementasi ini, `MemberOnboardingSubmitService` secara otomatis memutasi kolom `validation_status` menjadi `PENDING_REVIEW` setiap kali form `POST /member/onboarding` dikirimkan. Hal ini memungkinkan pengguna mempromosikan status mereka sendiri tanpa verifikasi admin.

Pada ONB-08:
- `MemberOnboardingSubmitService::submit()` diperbarui agar **hanya** memperbarui data profil anggota (nama, kontak, alamat, identitas) serta merekam waktu pengisian (`profile_completed_at`, `onboarding_submitted_at`).
- Kolom `status` dan `validation_status` **TIDAK PERNAH** dimutasi oleh form submission anggota.
- Anggota `PENDING/PENDING` tetap `PENDING/PENDING` setelah submit.
- Anggota `ACTIVE/ACTIVE` tetap `ACTIVE/ACTIVE` setelah submit.
- Hanya alur verifikasi maker-checker admin dan pengurus (**ONB-03**) yang memiliki wewenang mengubah status validasi keanggotaan.

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
- `BlockedUnknown` -> `blocked`

---

## 6. Antarmuka Pengguna (Kojayaku Onboarding UI)

Komponen frontend `resources/js/pages/Kojayaku/Onboarding.vue` telah disempurnakan:
- Menghilangkan tombol persetujuan mandiri (*self-approval CTA*) atau tombol aktivasi buatan.
- Menampilkan status siklus hidup dalam Bahasa Indonesia yang informatif:
  - **Menunggu Verifikasi Admin**: Menjelaskan berkas sedang dalam antrean verifikasi petugas koperasi.
  - **Menunggu Persetujuan Pengurus**: Menjelaskan data telah diverifikasi petugas dan menunggu persetujuan pengurus.
  - **Perlu Revisi**: Menampilkan catatan perbaikan yang dibutuhkan dan mengizinkan pembaruan formulir.
  - **Ditolak**: Menampilkan informasi penolakan pendaftaran.
  - **Aktif**: Menampilkan konfirmasi keanggotaan penuh dan tautan ke dasbor.

---

## 7. Verifikasi & Pengujian

Suite pengujian khusus dibuat di `tests/Feature/Onboarding/MemberFirstLoginLifecycleExperienceTest.php` yang mencakup 9 skenario komprehensif:

1. `test_derived_lifecycle_experience_mapping_resolves_all_canonical_and_inconsistent_states`:
   - Validasi pemetaan kanonikal 5 status valid.
   - Validasi fail-closed 7 pasangan kontradiktif/tidak dikenal.
2. `test_web_fortify_login_redirects_non_active_members_to_onboarding_and_active_to_dashboard`:
   - Pengujian login sandi web untuk semua status non-aktif mengarah ke onboarding.
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
   - Pengujian integritas payload API login untuk seluruh status siklus hidup.
8. `test_legacy_onboarding_submit_never_self_advances_status_preserving_onb03_authority`:
   - Memastikan `POST /member/onboarding` tidak memutasi `validation_status` maupun `status`.
9. `test_multi_tenant_isolation_member_experience_strictly_scoped_to_organization`:
   - Memastikan isolasi data multi-tenant dan boundary koperasi tetap terjaga.
