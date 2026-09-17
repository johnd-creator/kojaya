# ONB-07 — Google SSO Member Matching

Dokumentasi arsitektur dan spesifikasi implementasi untuk penautan identitas Google SSO ke data kanonikal anggota koperasi (`CooperativeMember`) pada Kojaya.

---

## 1. Ringkasan & Posisi Roadmap

Dalam roadmap Kojaya Onboarding:
- **ONB-01:** Member Data Contract ✅
- **ONB-02:** Google Form Alignment ✅
- **ONB-03:** Admin Verification Workflow ✅
- **ONB-04:** Backend Import Validator ✅
- **ONB-05:** Import Dry-Run / Preview ✅
- **ONB-06:** DEV Member Import ✅
- **ONB-07:** Google SSO Member Matching (Current)
- **ONB-08:** First Login / Activation (Next)
- **ONB-09:** Onboarding E2E (Future)

**Tujuan Utama ONB-07:**
Mengikat identitas eksternal Google (`provider_id` / `sub`) yang telah terotentikasi secara aman ke entitas `CooperativeMember` kanonikal yang **sudah ada sebelumnya** di dalam sistem (misal hasil import ONB-06 atau registrasi admin), **tanpa** membuat data anggota baru, **tanpa** mengubah status siklus hidup anggota, dan **tanpa** menyimpan kredensial/token OAuth.

---

## 2. Invarian Keamanan Identitas (Identity Invariants)

1. **Email Google adalah bootstrap identity matching candidate semata, bukan identitas permanen.**
   - Pencocokan email hanya digunakan pada kali pertama anggota mengaitkan akunnya (first-time match, Step 2).
   - Setelah tertaut, `provider_id` Google adalah identitas permanen eksternal (Step 1). Jika akun sudah tertaut, login langsung diproses berdasarkan `provider_id` bahkan jika sinyal `email_verified` Google pada sesi tersebut bernilai `false`.
   - Perubahan email di sisi Google pada sesi berikutnya tidak akan memicu rematch dan tidak akan mengubah email kanonikal anggota maupun `users.email`.
2. **Aturan Mutlak — Callback Google TIDAK BISA membuat anggota baru:**
   - Jika akun Google yang masuk tidak cocok dengan anggota kanonikal yang ada, sistem melakukan **fail-closed**.
   - Tidak ada baris baru di `cooperative_members`.
   - Tidak ada nomor anggota sementara (`TMP...`).
   - Tidak ada baris baru di `users`.
   - Tidak ada baris baru di `social_accounts`.
3. **Verifikasi Email Google Wajib pada First-Time Bootstrap:**
   - Sinyal `email_verified` atau `verified_email` dari Google OIDC info / tokeninfo wajib bernilai `true` saat melakukan pencocokan pertama kali (Step 2). Email yang belum terverifikasi oleh Google ditolak secara tegas.
   - Pada akun yang sudah terikat sebelumnya (Step 1), verifikasi email Google tidak lagi menjadi syarat penghalang karena identitas terikat secara permanen pada `provider_id`.
4. **Otoritas Data Akun Pengguna & Verifikasi Email Kanonikal:**
   - Login pengguna eksisting **TIDAK** memutasi `users.email_verified_at`, `users.email`, maupun `cooperative_members.email`.
   - Penautan eksplisit oleh pengguna yang sudah login (`/auth/google/link`) hanya akan menandai `users.email_verified_at` jika email Google terverifikasi DAN cocok secara persis dengan email pengguna kanonikal (`LOWER(TRIM(googleEmail)) === LOWER(TRIM(user->email))`). Jika email Google berbeda atau unverified, penautan akun sosial tetap tersimpan tetapi status verifikasi email kanonikal tidak diubah.
   - Nama pengguna (`users.name`) diambil dari data kanonikal koperasi (`nama_anggota` / `name`), bukan display name Google.
   - Email pengguna (`users.email`) diambil dari data kanonikal koperasi.
   - Organisasi (`users.organization_id`) diambil dari organisasi anggota koperasi.
   - Password pengguna diisi dengan string acak dengan entropi tinggi (64 karakter acak yang di-hash dengan bcrypt), sehingga tidak ada password default atau plaintext.
   - Role yang diberikan semata-mata adalah `Anggota`. Tidak ada role administratif (`Admin Koperasi`, `Pengurus Koperasi`, dll.) yang diberikan.
5. **Privasi & Keamanan Audit:**
   - Audit event: `member.google_sso_linked`.
   - Tidak mencatat NIK (`identity_number`), nomor rekening, maupun data PII sensitif.
   - `provider_id` Google dicatat dalam bentuk ringkasan aman (SHA-256 hash).
   - Tidak ada `access_token` maupun `refresh_token` OAuth yang disimpan di database.
   - Kegagalan pencatatan audit memicu rollback penuh pada seluruh transaksi first-time link.

---

## 3. Prioritas Pencocokan (Matching Priority Flow)

```text
Google OAuth Callback / Mobile ID Token
                   │
                   ▼
     Ekstrak Google provider_id (sub)
                   │
                   ▼
  [Step 1: Lookup Akun Sosial Eksisting]
  Query: social_accounts WHERE provider = 'google' AND provider_id = :providerId
                   │
         ┌─────────┴─────────┐
         ▼                   ▼
      [Ditemukan]       [Tidak Ditemukan]
         │                   │
         ▼                   ▼
  Login User terkait     [Step 2: First-Time Matching]
  (Tanpa rematch)       Validasi email terverifikasi
  Update timestamp      Normalisasi LOWER(TRIM(email))
  Audit login_success   Query: cooperative_members WHERE LOWER(TRIM(email)) = :email
                             │
                  ┌──────────┴──────────┐
                  ▼                     ▼
             [Count != 1]           [Count == 1]
                  │                     │
                  ▼                     ▼
             Fail-Closed          Periksa Kelayakan Status (Eligibility)
             (No member /         & Cek Konflik Akun
             Ambiguous)                 │
                              ┌─────────┴─────────┐
                              ▼                   ▼
                        [Tidak Lolos]          [Lolos]
                              │                   │
                              ▼                   ▼
                         Fail-Closed      [Atomic Linking Transaction]
                                          - Lock row member (lockForUpdate)
                                          - Verifikasi ulang ketiadaan konflik
                                          - Buat User (data kanonikal)
                                          - Assign role Anggota
                                          - Link member.user_id
                                          - Buat SocialAccount (tanpa token)
                                          - Mandatory Audit Log
                                          - Commit & Login
```

---

## 4. Matriks Kelayakan Anggota (Member Eligibility Matrix)

| Status | Validation Status | Aksi Matching | Efek Status Pasca-Match |
| :--- | :--- | :--- | :--- |
| `PENDING` | `PENDING` | **Diizinkan** | Tetap `PENDING` / `PENDING` |
| `PENDING` | `PENDING_VALIDATION` | **Diizinkan** | Tetap `PENDING` / `PENDING_VALIDATION` |
| `ACTIVE` | `ACTIVE` | **Diizinkan** | Tetap `ACTIVE` / `ACTIVE` |
| `INACTIVE` | `REVISION` | **Ditolak (Fail-Closed)** | Tidak ada perubahan |
| `INACTIVE` | `REJECTED` | **Ditolak (Fail-Closed)** | Tidak ada perubahan |
| `RESIGNED` | `RESIGNED` / State lain | **Ditolak (Fail-Closed)** | Tidak ada perubahan |

*Catatan Penting:*
Proses matching identitas **TIDAK** mengubah status siklus hidup anggota koperasi (`status` dan `validation_status` tidak boleh dimodifikasi). Aktivasi anggota adalah tanggung jawab ONB-08.

---

## 5. Matriks Penanganan Konflik (Conflict Matrix)

| Kondisi Konflik | Perilaku Sistem | Kode / Respon |
| :--- | :--- | :--- |
| Email Google tidak cocok dengan anggota kanonikal mana pun | Gagal terkendali. Tidak membuat member/user baru. | `MEMBER_NOT_FOUND_FOR_GOOGLE_ACCOUNT` |
| Email cocok dengan lebih dari 1 record anggota | Gagal terkendali. Ditolak untuk mencegah ambiguitas data. | `MEMBER_SSO_AMBIGUOUS` |
| Email anggota berstatus tidak layak (misal REVISION/REJECTED) | Gagal terkendali. Akun tidak ditautkan. | `MEMBER_NOT_ELIGIBLE_FOR_SSO` |
| Anggota kanonikal sudah memiliki `user_id` yang berbeda | Gagal terkendali. Menolak penimpaan kepemilikan akun. | `MEMBER_USER_LINK_CONFLICT` |
| Email Google bertabrakan dengan `users.email` lain yang tidak terafiliasi | Gagal terkendali. Menolak pengambilalihan akun sembarangan. | `USER_EMAIL_CONFLICT` |
| Google `provider_id` sudah tertaut ke `users` lain | Login sebagai user pemilik binding eksisting (Step 1) atau tolak linking baru. | `GOOGLE_ACCOUNT_ALREADY_LINKED` |
| Email Google tidak terverifikasi (`email_verified === false`) | Ditolak pada first-time bootstrap (Step 2). Tidak menghalangi login jika `provider_id` sudah terikat sah sebelumnya (Step 1). | `GOOGLE_EMAIL_NOT_VERIFIED` |

---

## 6. Transaksionalitas, Konkurensi & Penguncian Baris

Untuk mencegah kondisi balapan (*race condition*) seperti callback ganda yang terjadi secara bersamaan (*double callback*):
1. **Database Transaction & Row Locking:**
   - Seluruh proses pembuatan akun dan penautan dijalankan di dalam `DB::transaction()`.
   - Baris `CooperativeMember` dikunci secara eksklusif menggunakan `lockForUpdate()`.
2. **Double-Check Commit Time:**
   - Sebelum persistensi, sistem memeriksa kembali bahwa `member.user_id` masih `null`.
   - Memeriksa kembali bahwa `provider_id` belum didaftarkan oleh proses bersamaan di `social_accounts`.
   - Memeriksa kembali bahwa `email` belum terdaftar di tabel `users`.
3. **Database Unique Constraints:**
   - `cooperative_members.user_id` memiliki indeks unik (`cooperative_members_user_id_unique`).
   - `social_accounts(provider, provider_id)` memiliki indeks unik.
   - `users.email` memiliki indeks unik.
4. **Verifikasi Konkurensi:**
   - Diuji menggunakan tes konkurensi PostgreSQL riil (`GoogleSsoMemberMatchingConcurrencyTest`) yang menjalankan proses paralel secara bersamaan. Tepat satu worker berhasil menautkan akun, dan worker lainnya gagal terkendali tanpa duplikasi data.

---

## 7. Layanan & Antarmuka Terkait

- **`MemberGoogleSsoMatchingService`:** Layanan domain utama yang mengeksekusi Step 1 dan Step 2 pencocokan identitas.
- **`MemberGoogleSsoMatchResult`:** DTO immutable yang mengembalikan status penautan, user, social account, dan kode diagnostik.
- **`GoogleSsoService`:** Gerbang orkestrasi SSO yang mengintegrasikan pencocokan identitas untuk alur web Socialite dan login API mobile.
- **`GoogleSsoController`:** Controller web yang mengarahkan pengguna ke Google OAuth dan memproses callback dengan pesan error yang ramah privasi:
  `"Akun Google ini belum dapat dihubungkan ke akun anggota Kojaya. Silakan hubungi administrator koperasi."`
- **`AuthController::loginWithGoogle`:** Endpoint API mobile (`/api/auth/google/mobile`) untuk verifikasi ID token Google dan penautan identitas anggota.
