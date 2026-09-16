# Kojaya — Alur Kerja Verifikasi Admin & Persetujuan Anggota (ONB-03)

Dokumen ini mendefinisikan spesifikasi operasional dan arsitektur alur kerja verifikasi administratif oleh **Admin Koperasi** serta persetujuan akhir (*final approval*) oleh **Pengurus Koperasi** untuk penerimaan anggota koperasi baru (Kojaya Member Onboarding Phase 2).

---

## 1. Maksud & Ruang Lingkup (Purpose & Scope)

### 1.1 Tujuan
Menyelaraskan alur kerja verifikasi keanggotaan eksisting pada KojayaPro dengan:
- **ONB-01**: Kontrak Data Anggota Kanonikal yang telah dibekukan (`docs/onboarding/member-data-contract.md`).
- **ONB-02**: Spesifikasi Penyelarasan Google Form Intake yang telah dibekukan (`docs/onboarding/google-form-alignment.md`).

### 1.2 Ruang Lingkup
- Tinjauan data calon anggota setelah record kanonikal dipersist dengan status awal `PENDING`.
- Verifikasi administratif kelayakan berkas dan data identitas pelamar oleh Admin Koperasi.
- Konsumsi referensi pegawai yang telah ter-resolve secara definitif pada tahap pra-persistensi (`employee_id` terisi atau `NULL` murni non-karyawan).
- Deteksi duplikasi dan penanganan konflik data dengan prinsip *fail-closed*.
- Penetapan data pengayaan administratif (*admin enrichment*): nomor anggota, jenis keanggotaan, dan tanggal bergabung.
- Penegakan tata kelola *Maker-Checker* (`admin_validated_by != validated_by`).
- Persetujuan akhir (*final approval*) atau permintaan perbaikan (*revision*) atau penolakan (*reject*) oleh Pengurus Koperasi.
- Penyelarasan visibilitas tombol aksi antarmuka (UI) dengan aturan siklus hidup backend.
- Penerbitan log audit lengkap dan dispatch notifikasi internal/anggota.

### 1.3 Batasan Tegas (Out of Scope)
- **Bukan Parser/Importer CSV**: Parsing berkas batch CSV dan validasi skema batch merupakan tanggung jawab `ONB-04 (Backend Import Validator)`.
- **Bukan Resolusi NIP Mentah Pra-Persistensi**: Resolusi `employee_number` terhadap master karyawan `employees.employee_code` dan penanganan antrean NIP tak ter-resolve dimiliki sepenuhnya oleh `ONB-04` sebelum persistensi.
- **Bukan Integrasi Langsung Google Forms API**: Asupan form telah diselaraskan melalui format ekspor spreadsheet pada ONB-02.
- **Bukan Perubahan Skema Database**: Menggunakan kolom dan relasi yang sudah ada pada skema database saat ini tanpa migrasi baru.
- **Bukan Pengelolaan Finansial Awal**: Wizard saldo awal anggota (simpanan pokok/wajib) dikelola terpisah melalui modul pembukuan kas/bank.
- **Bukan Implementasi OAuth**: Integrasi akun pengguna dengan penyedia identitas Google ditangani melalui alur SSO/Google OAuth eksisting.

---

## 2. Sumber Kebenaran (Source of Truth)

Alur kerja verifikasi ini tunduk secara hirarkis pada dokumen dan artefak kode berikut:

### 2.1 Dokumen Spesifikasi Beku (Frozen Upstream Documents)
1. [`docs/onboarding/member-data-contract.md`](member-data-contract.md) — Kontrak Data Anggota Kanonikal (12 kolom kanonikal, 8 field pelamar, 4 field pengayaan).
2. [`docs/onboarding/member-import-template.csv`](member-import-template.csv) — Template CSV batch impor Phase-2.
3. [`docs/onboarding/google-form-alignment.md`](google-form-alignment.md) — Pemetaan form asupan publik (7 wajib + 1 opsional).

### 2.2 Komponen Kode & Skema Otoritatif
- Model Eloquent:
  - `app/Models/CooperativeMember.php` — Status siklus hidup operasional dan status tata kelola validasi, enkripsi PII, dan relasi.
  - `app/Models/Employee.php` — Master data pegawai untuk pencocokan NIP (`employee_code`).
  - `app/Models/User.php` — Identitas akun sistem dan relasi penautan akun anggota.
  - `app/Models/AuditLog.php` — Pencatatan log audit transisi status dan aksi keamanan.
- Lapisan Layanan (Services):
  - `app/Services/Cooperative/MemberValidationService.php` — Validasi domain, aturan Maker-Checker, dan pemicu notifikasi.
  - `app/Services/Cooperative/MemberStatusTransitionService.php` — Mesin transisi state dengan row locking atomik, evaluasi permission, dan audit trail.
  - `app/Services/Cooperative/MemberAccessRevocationService.php` — Penarikan token sesi Sanctum dan pencabutan role pengguna saat revisi/penolakan.
  - `app/Services/Cooperative/CooperativeNotificationDispatcher.php` — Pengiriman notifikasi berbasis peran dan anggota.
  - `app/Services/AuditLogService.php` — Layanan audit terpusat.
- HTTP & Otorisasi:
  - `app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php` — Controller endpoint verifikasi dan approval.
  - `app/Http/Requests/Cooperative/ValidateCooperativeMemberRequest.php` — Validasi request verifikasi admin.
  - `app/Http/Requests/Cooperative/ApproveCooperativeMemberRequest.php` — Validasi request approval final pengurus.
  - `app/Http/Requests/Cooperative/RequestCooperativeMemberRevisionRequest.php` — Validasi permohonan revisi.
  - `app/Http/Requests/Cooperative/RejectCooperativeMemberRequest.php` — Validasi penolakan keanggotaan.
  - `app/Policies/CooperativeMemberPolicy.php` — Kebijakan otorisasi akses modul anggota.
  - `app/Enums/PermissionEnum.php` — Definisi enum permission sistem.
  - `database/seeders/RolePermissionSeeder.php` — Konfigurasi pemetaan role dan permission.
- Antarmuka Staf (UI):
  - `resources/js/pages/Cooperative/Members/Show.vue` — Halaman detail anggota dengan tombol aksi verifikasi dan persetujuan yang telah diselaraskan dengan state backend.

---

## 3. Inventarisasi Implementasi Eksisting (Existing Implementation Inventory)

| Komponen Sistem | Status Implementasi | Deskripsi & Bukti Kode |
| :--- | :---: | :--- |
| **MemberValidationService** | `ALREADY IMPLEMENTED` | Mengatur alur verifikasi admin (`verifyByAdmin`), approval akhir (`approveFinal`), permohonan revisi (`requestRevision`), dan penolakan (`reject`). Memiliki guard Maker-Checker `assertApproverIsNotVerifier()`. |
| **MemberStatusTransitionService** | `ALREADY IMPLEMENTED` | Mengelola transaksi DB terisolasi (`DB::transaction`), penguncian baris konkurensi (`lockForUpdate()`), validasi state asal yang diizinkan (`assertAllowedSource`), mutasi status, penugasan/pencabutan role `Anggota`, dan pencatatan audit log terstruktur. |
| **Role & Permissions (RBAC)** | `ALREADY IMPLEMENTED` | Otorisasi berbasis permission: `verify_cooperative_member`, `validate_cooperative_member`, dan `approve_cooperative_member`. Dipetakan secara ketat pada seeder dan Form Request. |
| **Maker-Checker Enforcement** | `ALREADY IMPLEMENTED` | Ditegakkan langsung pada inti domain: `$member->admin_validated_by !== $approver->id`. Melempar `ValidationException` jika dilanggar, mencegah single-point self-approval. |
| **Audit Logging** | `ALREADY IMPLEMENTED` | Terintegrasi penuh ke `AuditLogService` dengan aksi `member.status.transitioned` dan `member.access.revoked`, merekam state lama, state baru, alasan, aktor, IP address, dan user agent. |
| **Notification Infrastructure** | `ALREADY IMPLEMENTED` | Terintegrasi ke `CooperativeNotificationDispatcher` melalui callback pasca-komit (`DB::afterCommit`) untuk event `memberAdminVerified`, `memberFinalApproved`, `memberRevisionRequested`, dan `memberRejected`. |
| **Access Revocation Engine** | `ALREADY IMPLEMENTED` | `MemberAccessRevocationService` menarik seluruh token Sanctum aplikasi anggota secara atomik saat pengajuan ditolak atau dikembalikan untuk revisi. |
| **Automated Test Suite** | `ALREADY IMPLEMENTED` | Diuji pada `tests/Feature/Cooperative/CooperativeMemberValidationTest.php` mencakup skenario verifikasi admin, approval pengurus, penolakan hak akses admin, panjang minimum catatan, pencegahan verifikasi ganda, penegakan Maker-Checker (termasuk System Admin), dan rekaman audit log. |
| **Staff Web UI & Controller** | `ALREADY IMPLEMENTED` | Halaman Inertia Vue `resources/js/pages/Cooperative/Members/Show.vue` dan controller `CooperativeMemberValidationController.php` menyediakan aksi interaktif verifikasi, persetujuan, revisi, dan penolakan dengan visibilitas yang selaras dengan siklus hidup backend. |

---

## 4. Batasan Asupan Data Pelamar (Applicant Intake Boundary)

Alur kerja verifikasi admin mengonsumsi rekaman anggota yang telah dipersist oleh tahap impor staging (ONB-04) berdasarkan 8 field data pelamar yang berasal dari formulir asupan (ONB-02):

| No | Nama Kolom | Tipe Data | Wajib/Opsional | Aturan Konsumsi & Batasan Integritas Admin |
| :---: | :--- | :---: | :---: | :--- |
| 1 | `full_name` | Teks | Wajib | Nama lengkap pelamar sesuai kartu identitas. Admin dilarang mengubah ejaan nama secara sepihak tanpa bukti dokumen sah. |
| 2 | `email` | Teks / Email | Wajib | Alamat email kandidat bootstrap (Google OAuth). Bersifat *read-only* bagi admin selama verifikasi administratif; perubahan email memerlukan verifikasi ulang identitas akun. |
| 3 | `phone_number` | Teks | Wajib | Nomor telepon/WhatsApp aktif. Format dinormalisasi (E.164 / `08...`). |
| 4 | `identity_number` | Teks (16 digit) | Wajib | Nomor Induk Kependudukan (NIK). Wajib divalidasi struktur numerik 16 digit. Tersimpan terenkripsi dengan blind index (`identity_number_enc`, `identity_number_bidx`). |
| 5 | `gender` | Enum (`L`/`P`) | Wajib | Jenis kelamin pelamar. Nilai terkontrol. |
| 6 | `company_code` | Teks (Kunci) | Wajib | Kode unit/perusahaan afiliasi pelamar. Digunakan untuk menurunkan `company_name` secara deterministik. |
| 7 | `address` | Teks | Wajib | Alamat tempat tinggal pelamar sesuai KTP atau domisili riil. |
| 8 | `employee_number` | Teks (NIP) | Opsional | Referensi NIP pegawai. Di-resolve pada tahap pra-persistensi oleh ONB-04. Pada ONB-03, field ini tercermin sebagai relasi foreign key `employee_id` yang sah, atau `NULL` bagi non-karyawan. |

### Prinsip Integritas Data Pelamar:
- **No Silent Rewrite**: Admin Koperasi **DILARANG KERAS** mengubah data identitas pokok pelamar (nama, email, NIK, tautan pegawai) secara diam-diam.
- **Traceability**: Segala bentuk penyesuaian atau pengkoreksian administratif wajib tercatat di dalam log audit dengan riwayat nilai sebelum dan sesudah perubahan.

---

## 5. Daftar Periksa Verifikasi Admin (Admin Verification Checklist)

Sebelum Admin Koperasi menekan tombol **Verifikasi** (`verifyByAdmin`), seluruh butir periksa berikut wajib dipenuhi:

```text
[ ] 1. Identitas Pokok: Nama lengkap terisi, wajar, dan sesuai dengan dokumen pendukung (KTP/kartu identitas).
[ ] 2. Alamat Email: Format email valid dan terdaftar sebagai kandidat bootstrap Google SSO.
[ ] 3. Kontak Telepon: Nomor ponsel valid, dapat dihubungi, dan memiliki awalan format Indonesia yang benar.
[ ] 4. Validasi Struktural NIK: NIK tepat 16 digit numerik tanpa karakter khusus.
       (PENTING: Validasi struktural adalah pemeriksaan format data, BUKAN klaim verifikasi instansi kependudukan/Dukcapil).
[ ] 5. Nilai Terkontrol Gender: Nilai bernilai tepat 'L' (Laki-laki) atau 'P' (Perempuan).
[ ] 6. Referensi Perusahaan: Nilai company_code valid terhadap master unit/perusahaan (company_name otomatis diturunkan).
[ ] 7. Alamat Domisili: Alamat tempat tinggal lengkap dan memadai secara administratif.
[ ] 8. Status Tautan Pegawai (employee_id):
       - Jika Non-Karyawan: Tidak ada referensi NIP saat pendaftaran; employee_id bernilai NULL tanpa ambiguitas.
       - Jika Karyawan: NIP telah ter-resolve unik pada pra-persistensi; employee_id terhubung ke data master Employee yang sah.
       (Catatan: Rekaman dengan NIP tak ter-resolve TIDAK PERNAH sampai ke tahap ini karena diblokir fail-closed di ONB-04).
[ ] 9. Penapisan Duplikasi: Tidak ditemukan benturan data pada email, NIK, tautan pegawai, maupun nomor anggota aktif.
[ ] 10. Penetapan Pengayaan:
       - Nomor anggota (member_number) telah ditetapkan atau siap digenerate otomatis.
       - Jenis keanggotaan (membership_type) telah ditentukan (AB untuk karyawan / ALB untuk non-karyawan).
       - Tanggal bergabung (join_date) telah diverifikasi.
[ ] 11. Catatan Verifikasi: Admin dapat menyertakan catatan administratif (opsional jika valid).
```

---

## 6. Resolusi Referensi Pegawai / NIP (Employee Reference Resolution)

### 6.1 Batas Otoritas Pra-Persistensi (Pre-Persistence Boundary)
Resolusi nilai `employee_number` terhadap tabel master karyawan `employees.employee_code` berada sepenuhnya di bawah kendali **ONB-04 (Backend Import Validator)** pada tahap pra-persistensi.

Sistem memberlakukan aturan fail-closed dengan 3 kasus terdefinisi:

```text
Kasus A: Tidak Ada NIP yang Dimasukkan (Blank / Null)
   employee_number = blank / null
         │
         ▼
   Pelamar diperlakukan sebagai Non-Karyawan sah
   cooperative_members.employee_id = NULL
         │
         ▼
   PERSISTENCE DIIZINKAN (status = PENDING, validation_status = PENDING)
   -> Diserahkan ke ONB-03 untuk verifikasi administratif.

─────────────────────────────────────────────────────────────────────────

Kasus B: NIP Dimasukkan dan Ter-resolve Unik
   employee_number terisi
         │
         ▼
   Pencarian Eksak: employees.employee_code
   (dengan filter: employees.organization_id == input.organization_id)
         │
         ▼
   Ditemukan tepat satu pegawai sah: employees.id
   cooperative_members.employee_id = employees.id
         │
         ▼
   PERSISTENCE DIIZINKAN (status = PENDING, validation_status = PENDING)
   -> Diserahkan ke ONB-03 untuk verifikasi administratif.

─────────────────────────────────────────────────────────────────────────

Kasus C: NIP Dimasukkan Tetapi Tidak Ter-resolve (Unresolved / Ambiguous)
   (Mencakup: tidak ditemukan, pencocokan ganda, beda unit organisasi, atau referensi tidak valid)
         │
         ▼
   UNRESOLVED EMPLOYEE REFERENCE -> FAIL-CLOSED MUTLAK
         │
         ▼
   BLOCKED DI TINGKAT PRA-PERSISTENSI ONB-04
   (Antrean Validasi / Dry-Run / Staging Review)
         │
         ▼
   DILARANG KERAS DIPERSIST KE TABEL cooperative_members
   DILARANG diubah secara diam-diam menjadi employee_id = NULL
   DILARANG disimpan di kolom notes
```

### 6.2 Makna Tunggal `employee_id = NULL` Pasca-Persistensi
Dengan aturan fail-closed di atas, untuk seluruh rekaman anggota yang berhasil dipersist ke tabel `cooperative_members`:
$$\text{cooperative\_members.employee\_id} = \text{NULL} \iff \text{Tidak ada referensi karyawan yang dimasukkan pelamar (Non-Karyawan)}$$
Tidak ada kerancuan antara pelamar non-karyawan dengan pelamar karyawan yang gagal ter-resolve.

### 6.3 Peninjauan Manual (*Manual Review*) NIP Tak Ter-resolve
1. Peninjauan manual NIP tak ter-resolve diselesaikan sepenuhnya pada antrean validasi/impor ONB-04 sebelum persistensi kanonikal anggota.
2. Operator/Admin melakukan klarifikasi ke bagian SDM/HR:
   - Jika karyawan sah: data pegawai ditambahkan ke master `employees`, baris diimpor ulang/divalidasi ulang hingga `employee_id` ter-resolve.
   - Jika pelamar salah mengira dirinya karyawan: nilai NIP dihapus atas persetujuan pelamar, baris divalidasi ulang sebagai Kasus A (Non-Karyawan), lalu dipersist dengan `employee_id = NULL`.
3. Setelah persistensi berhasil, barulah alur verifikasi ONB-03 mengambil alih.
4. **Kebijakan Skema Database**: **TIDAK memerlukan penambahan kolom database baru**.

---

## 7. Penanganan Duplikasi & Konflik Data (Duplicate & Conflict Handling)

### 7.1 Matriks Penanganan Benturan Data
Sistem menerapkan kebijakan ketat: **FAIL-CLOSED, NO SILENT MERGE, NO SILENT OVERWRITE, AUDIT, MANUAL REVIEW**.

| Jenis Konflik | Kondisi Terdeteksi | Status Rekaman | Tindakan Sistem & Antarmuka Admin |
| :--- | :--- | :---: | :--- |
| **Duplikasi Email** | Email pelamar sudah terdaftar pada tabel `users` atau `cooperative_members` lain. | `PENDING` (Blocked) | Sistem menampilkan peringatan benturan akun. Admin dilarang menyetujui. Memerlukan penelusuran apakah pelamar telah memiliki akun sebelumnya atau salah ketik. |
| **Duplikasi NIK** | Hash blind index NIK (`identity_number_bidx`) cocok dengan anggota yang sudah ada. | `PENDING` (Blocked) | Sistem menolak pembuatan/verifikasi anggota aktif ganda. Diperiksa apakah merupakan pendaftaran ulang dari anggota yang pernah mengundurkan diri (*re-join*). |
| **Benturan Tautan Pegawai** | `employee_id` yang terhubung telah ditautkan ke anggota koperasi aktif lain. | `PENDING` (Blocked) | Masuk antrean review khusus benturan referensi pegawai. Dilarang menautkan satu pegawai ke lebih dari satu profil anggota koperasi aktif. |
| **Duplikasi Nomor Anggota** | `member_no` yang diusulkan telah dipakai oleh anggota lain. | `PENDING` (Blocked) | Input ditolak oleh database unique constraint. Admin wajib menetapkan nomor anggota baru atau menggunakan generator otomatis sistem. |
| **Ketidakcocokan Identitas (Identity Mismatch)** | Email Google OAuth yang terautentikasi berbeda dengan email yang diisi pada profil pendaftaran. | `PENDING` (Blocked) | Dilarang menautkan akun pengguna secara otomatis. Admin melakukan klarifikasi kepemilikan akun. |

### 7.2 Tindakan yang Dilarang (Forbidden Actions)
- Menggabungkan (*merge*) dua akun atau rekaman berbeda secara otomatis di belakang layar.
- Menimpa (*overwrite*) data anggota aktif yang sudah ada dengan data pelamar baru.
- Melakukan *bypass* verifikasi saat tanda bahaya duplikasi aktif.

---

## 8. Aturan Pengayaan Administratif (Admin Enrichment Rules)

Sesuai kontrak beku ONB-01, terdapat 4 field pengayaan yang berada di bawah otoritas penuh Admin Koperasi:

```text
┌─────────────────────────┬───────────────────────────────────┬────────────────────────────────────────────────────────┐
│ Field Pengayaan         │ Otoritas / Kepemilikan            │ Aturan Bisnis & Validasi                               │
├─────────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────┤
│ member_number           │ Admin / Sistem Terkontrol         │ Format nomor anggota resmi koperasi. Dapat diinput      │
│ (member_no, no_anggota) │                                   │ manual atau dihasilkan otomatis oleh generator sistem. │
│                         │                                   │ Wajib unik per organisasi koperasi.                    │
├─────────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────┤
│ membership_type         │ Admin Terkontrol (Default AB/ALB) │ Menentukan hak suara dan jenis perlakuan:              │
│ (jenis_anggota)         │                                   │ - AB  : Anggota Biasa (Karyawan terafiliasi,           │
│                         │                                   │         employee_id terisi sah).                       │
│                         │                                   │ - ALB : Anggota Luar Biasa (Mitra / Umum,              │
│                         │                                   │         employee_id bernilai NULL).                    │
├─────────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────┤
│ join_date               │ Admin Terkontrol                  │ Tanggal resmi efektif terdaftar sebagai anggota.       │
│ (joined_at)             │                                   │ Standar default adalah tanggal hari verifikasi/impor.   │
├─────────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────┤
│ notes                   │ Admin Internal Notes              │ Catatan hasil pemeriksaan berkas atau justifikasi.     │
│ (admin_validation_notes)│                                   │ DILARANG digunakan sebagai tempat menyimpan NIP mentah │
│                         │                                   │ atau metadata konflik identitas.                       │
└─────────────────────────┴───────────────────────────────────┴────────────────────────────────────────────────────────┘
```

---

## 9. Pemetaan Status Siklus Hidup (Lifecycle State Mapping)

Sistem memisahkan status operasional (`status`) dengan status tata kelola validasi (`validation_status`) pada model `CooperativeMember`:

### 9.1 Definisi Konstanta Status
- **Operasional (`status`)**:
  - `PENDING`: Rekaman baru atau dalam proses peninjauan, belum aktif sebagai anggota penuh.
  - `ACTIVE`: Anggota koperasi resmi yang aktif dan berhak menggunakan layanan.
  - `INACTIVE`: Anggota yang ditolak, meminta revisi, atau dinonaktifkan sementara.
  - `RESIGNED`: Anggota yang telah resmi mengundurkan diri.
- **Tata Kelola Validasi (`validation_status`)**:
  - `VALIDATION_PENDING` (`'PENDING'`): Data masuk, menunggu verifikasi awal oleh Admin Koperasi.
  - `VALIDATION_PENDING_REVIEW` (`'PENDING_VALIDATION'`): Telah diverifikasi Admin Koperasi, menunggu persetujuan akhir Pengurus Koperasi.
  - `VALIDATION_ACTIVE` (`'ACTIVE'`): Telah disetujui final oleh Pengurus Koperasi.
  - `VALIDATION_INACTIVE` (`'INACTIVE'`): Status non-aktif umum.
  - `VALIDATION_REVISION` (`'REVISION'`): Berkas dikembalikan ke pelamar untuk perbaikan.
  - `VALIDATION_REJECTED` (`'REJECTED'`): Pengajuan ditolak definitif.
  - `VALIDATION_RESIGNED` (`'RESIGNED'`): Telah mengundurkan diri.

### 9.2 Diagram State Transition
```text
  [ Pendaftaran Baru / Impor Staging yang Lolos Pra-Persistensi ]
                                │
                                ▼
                      ┌───────────────────┐
                      │  status: PENDING  │ ◄───────────────────────────┐
                      │  val: PENDING     │                             │
                      └─────────┬─────────┘                             │
                                │                                       │
                                │ verifyByAdmin()                       │
                                ▼                                       │
                      ┌───────────────────┐                             │
                      │  status: PENDING  │                             │
                      │  val: PENDING_VAL │                             │
                      └───────┬───┬───┬───┘                             │
                              │   │   │                                 │
                 ┌────────────┘   │   └──────────────┐                  │
                 │ reject()       │ requestRevision()│ approveFinal()   │
                 ▼                ▼                  ▼                  │
            ┌───────────┐  ┌───────────┐      ┌─────────────┐           │
            │ INACTIVE  │  │ INACTIVE  │      │   ACTIVE    │           │
            │ REJECTED  │  │ REVISION  │      │   ACTIVE    │           │
            └───────────┘  └─────┬─────┘      └─────────────┘           │
                                 │                                      │
                                 │ Data diperbaiki & diverifikasi ulang │
                                 └──────────────────────────────────────┘
```

### 9.3 Matriks Transisi Status yang Valid
| Operasi / Transisi | State Awal (`status`, `validation_status`) | State Tujuan (`status`, `validation_status`) | Aktor Diizinkan |
| :--- | :--- | :--- | :--- |
| **Admin Verify** | `['PENDING', 'PENDING']` atau `['INACTIVE', 'REVISION']` | `['PENDING', 'PENDING_VALIDATION']` | Admin Koperasi (`verify_cooperative_member`) |
| **Final Approve** | `['PENDING', 'PENDING_VALIDATION']` | `['ACTIVE', 'ACTIVE']` | Pengurus Koperasi (`approve_cooperative_member`) |
| **Request Revision**| `['PENDING', 'PENDING_VALIDATION']` | `['INACTIVE', 'REVISION']` | Admin / Pengurus (`verify` / `validate`) |
| **Reject** | `['PENDING', 'PENDING_VALIDATION']` | `['INACTIVE', 'REJECTED']` | Admin / Pengurus (`verify` / `validate` / `approve`) |

Setiap percobaan transisi dari luar state awal yang terdaftar akan digagalkan oleh mesin transisi (`assertAllowedSource`) dengan exception `ValidationException: Transisi lifecycle tidak valid dari state anggota saat ini.`.

---

## 10. Aturan Tata Kelola Maker-Checker (Maker-Checker Rule)

### 10.1 Prinsip Dasar
Untuk mencegah fraud, konflik kepentingan, dan persetujuan sepihak, sistem KojayaPro memberlakukan aturan pemisahan tugas (*Segregation of Duties*):
$$\text{admin\_validated\_by} \neq \text{validated\_by}$$

### 10.2 Mekanisme Penegakan Teknis
1. Saat Admin Koperasi memverifikasi anggota, identitas aktor disimpan pada kolom `cooperative_members.admin_validated_by`.
2. Saat proses `approveFinal` dijalankan, metode `MemberValidationService::assertApproverIsNotVerifier()` memeriksa kesamaan ID aktor:
   ```php
   if ($member->admin_validated_by !== null && (string) $member->admin_validated_by === (string) $approver->id) {
       throw ValidationException::withMessages([
           'approved_by' => 'Verifier administrasi tidak boleh menjadi approver final.',
       ]);
   }
   ```
3. **Pengecualian Nihil**: Aturan ini berlaku mutlak. Pengguna dengan peran `System Admin` sekalipun **TIDAK DAPAT** menyetujui final permohonan yang diverifikasi oleh akunnya sendiri.
4. **Pencegahan Akses Paralel**: Aksi aktivasi langsung melalui `CooperativeMemberController::activate()` dilindungi oleh guard state `INACTIVE -> ACTIVE`, sehingga tidak dapat dimanfaatkan untuk mem-bypass alur verifikasi onboarding `PENDING -> ACTIVE`.

---

## 11. Matriks Peran & Izin (Role / Permission Matrix)

Berdasarkan konfigurasi `RolePermissionSeeder` dan otorisasi aplikasi:

| Peran Pengguna (Role) | `view_cooperative_member` | `verify_cooperative_member` | `approve_cooperative_member` | `manage_cooperative_member` | Kewenangan dalam Alur Onboarding |
| :--- | :---: | :---: | :---: | :---: | :--- |
| **Admin Koperasi** | Ya | **Ya** | **Tidak** | Ya | Memeriksa berkas, menginput data pengayaan, dan melakukan verifikasi administratif awal (*Maker*). Dilarang menyetujui final. |
| **Pengurus Koperasi** | Ya | **Tidak** (Kecuali diberikan khusus) | **Ya** | Ya | Memeriksa hasil verifikasi admin dan memberikan persetujuan akhir (*Checker* / *Approver*). |
| **Manajer Koperasi** | Ya | **Tidak** | **Tidak** | Tidak | Hanya memiliki hak pantau status operasional keanggotaan. Tidak berhak memverifikasi maupun menyetujui anggota. |
| **System Admin** | Ya | Ya (Super Admin) | Ya (Super Admin) | Ya | Pemeliharaan sistem dan penanganan eskalasi. Tunduk pada aturan Maker-Checker (tidak boleh *self-approve*). |
| **Kasir Koperasi** | Tidak | Tidak | Tidak | Tidak | Tidak memiliki akses ke data maupun aksi keanggotaan. |

---

## 12. Tindakan Verifikasi Admin & Visibilitas UI (Admin Actions & UI Visibility)

### 12.1 Penyelarasan Visibilitas UI dengan Backend Lifecycle
Untuk menjamin integritas alur kerja dan mencegah kesalahan operasional (UI bug), tampilan aksi pada halaman `resources/js/pages/Cooperative/Members/Show.vue` diselaraskan secara presisi dengan transisi yang diizinkan di backend:

| Status Siklus Hidup Anggota | Tombol "Verifikasi" (`verifyMember`) | Tombol "Approve Final" (`approveFinalAction`) | Menu "Minta revisi" & "Tolak anggota" |
| :--- | :---: | :---: | :---: |
| `PENDING` (Tahap Verifikasi Admin) | **TAMPIL** (jika `canVerifyMember`) | SEMBUNYI | **SEMBUNYI** |
| `REVISION` (Tahap Perbaikan Berkas) | **TAMPIL** (jika `canVerifyMember`) | SEMBUNYI | **SEMBUNYI** |
| `PENDING_VALIDATION` (Tahap Review Pengurus) | SEMBUNYI | **TAMPIL** (jika `canApproveMember`) | **TAMPIL** (jika `canReviewMember`) |
| `ACTIVE` / `INACTIVE` / `REJECTED` | SEMBUNYI | SEMBUNYI | SEMBUNYI |

Kondisi template pada `Show.vue` ditegakkan sebagai:
```vue
<template v-if="canReviewMember && isFinalApprovalReady">
    <!-- Minta revisi & Tolak anggota hanya muncul saat isFinalApprovalReady === true -->
</template>
```

### 12.2 Verifikasi Administratif (`verifyByAdmin`)
- **Prasyarat**: Status anggota berada pada `VALIDATION_PENDING` atau `VALIDATION_REVISION`.
- **Dampak Database**:
  - `status` tetap `'PENDING'`.
  - `validation_status` menjadi `'PENDING_VALIDATION'`.
  - `admin_validated_at` diisi timestamp saat ini.
  - `admin_validated_by` diisi ID Admin Koperasi yang memverifikasi.
  - `admin_validation_notes` diisi catatan admin (jika ada).
- **Notifikasi**: Sistem mengirimkan notifikasi internal kepada seluruh Pengurus Koperasi di unit tersebut bahwa anggota menunggu *final approval*.

---

## 13. Persetujuan Akhir Pengurus (Final Approval)

### 13.1 Prasyarat Eksekusi
- Pengguna memiliki hak otorisasi `approve_cooperative_member` dan role `Pengurus Koperasi` atau `System Admin`.
- Anggota telah berstatus `validation_status === 'PENDING_VALIDATION'`.
- Aktor approver berbeda dengan aktor pada `admin_validated_by`.

### 13.2 Dampak Bisnis & Efek Samping
- **Status Siklus Hidup**:
  - `status` bermutasi menjadi `'ACTIVE'`.
  - `validation_status` bermutasi menjadi `'ACTIVE'`.
- **Jejak Audit Approval**:
  - `validated_at` diisi timestamp saat ini.
  - `validated_by` diisi ID Pengurus Koperasi approver.
  - `validation_notes` diisi catatan persetujuan.
- **Penugasan Peran Sistem (Role Assignment)**:
  - Jika anggota terhubung ke akun pengguna (`user_id` tidak null), sistem otomatis memberikan role Spatie `'Anggota'` kepada pengguna tersebut.
- **Notifikasi Anggota**:
  - Sistem mengirimkan notifikasi resmi kepada pengguna anggota bahwa status keanggotaannya telah aktif dan dapat mulai mengakses layanan koperasi.

---

## 14. Alur Permintaan Revisi (Revision Flow)

### 14.1 Prasyarat & Validasi
- Dilakukan saat anggota berada pada state `PENDING_VALIDATION` dan ditemukan ketidaksesuaian data yang memerlukan klarifikasi atau unggah ulang dari calon anggota.
- Aktor wajib menyertakan alasan/catatan perbaikan (`notes`) dengan panjang minimal **5 karakter** dan maksimal **1000 karakter**.

### 14.2 Dampak Sistem
- `status` bermutasi menjadi `'INACTIVE'`.
- `validation_status` bermutasi menjadi `'REVISION'`.
- `validated_at` diisi waktu saat ini, `validated_by` diisi ID aktor, dan `validation_notes` diisi instruksi perbaikan.
- **Keamanan Akses**:
  - Role `'Anggota'` dicabut dari akun pengguna terkait.
  - Seluruh token Sanctum aktif milik pengguna ditarik/dihapus secara atomik oleh `MemberAccessRevocationService`.
- **Notifikasi**:
  - Anggota menerima notifikasi peringatan berisi rincian catatan revisi agar dapat melengkapi kembali datanya.

---

## 15. Alur Penolakan Pengajuan (Rejection Flow)

### 15.1 Prasyarat & Validasi
- Dilakukan saat anggota berada pada state `PENDING_VALIDATION` jika permohonan keanggotaan tidak memenuhi syarat mendasar koperasi atau terbukti mengandung manipulasi data.
- Catatan alasan penolakan (`notes`) **wajib diisi** (minimal 5 karakter, maksimal 1000 karakter).

### 15.2 Dampak Sistem
- `status` bermutasi menjadi `'INACTIVE'`.
- `validation_status` bermutasi menjadi `'REJECTED'`.
- `validated_at` diisi waktu saat ini, `validated_by` diisi ID penolak, dan `validation_notes` diisi alasan penolakan.
- **Keamanan Akses**:
  - Role `'Anggota'` dicabut secara permanen.
  - Seluruh token akses Sanctum ditarik.
  - Rekaman ditutup dan tidak dapat diaktifkan kembali secara implisit.
- **Notifikasi**:
  - Calon anggota menerima notifikasi resmi mengenai keputusan penolakan pengajuan keanggotaan.

---

## 16. Persyaratan Audit (Audit Requirements)

Setiap mutasi status siklus hidup anggota koperasi wajib menghasilkan entri audit yang permanen pada tabel `audit_logs` melalui `AuditLogService`.

### 16.1 Struktur Muatan Audit (`payload`)
Entri audit mencatat:
- `action`: `'member.status.transitioned'`.
- `module`: `'cooperative.lifecycle'`.
- `subject_type`: `App\Models\CooperativeMember`.
- `subject_id`: ID anggota terkait.
- `user_id`: ID aktor yang menjalankan perintah.
- `organization_id`: ID organisasi koperasi tempat anggota terdaftar.
- `actor_roles`: Daftar role yang disandang aktor saat aksi terjadi.
- `old_values`: Array berisi status sebelum transisi (`status`, `validation_status`).
- `new_values`: Array berisi status setelah transisi (`status`, `validation_status`, `action`).
- `reason`: Alasan atau catatan operasional yang diinput oleh aktor.
- `ip_address` & `user_agent`: Konteks jaringan dari HTTP request.

---

## 17. Perilaku Notifikasi (Notification Behavior)

Notifikasi ditangani oleh `CooperativeNotificationDispatcher` yang dieksekusi secara aman setelah transaksi database berhasil di-commit (`DB::afterCommit`):

```text
┌─────────────────────────┬───────────────────────────────┬──────────────────────┬────────────────────────────────────────────────────────┐
│ Metode Dispatcher       │ Target Penerima               │ Tingkat / Tipe       │ Ringkasan Pesan                                        │
├─────────────────────────┼───────────────────────────────┼──────────────────────┼────────────────────────────────────────────────────────┤
│ memberAdminVerified     │ Role: Pengurus Koperasi       │ Warning / Info       │ "Approval final anggota diperlukan: [Nama] sudah       │
│                         │ (dalam unit organisasi sama)  │                      │ diverifikasi Admin Koperasi dan menunggu approval."    │
├─────────────────────────┼───────────────────────────────┼──────────────────────┼────────────────────────────────────────────────────────┤
│ memberFinalApproved     │ User Anggota ($member->user)  │ Success              │ "Keanggotaan disetujui: Selamat! Keanggotaan koperasi  │
│                         │                               │                      │ Anda sudah aktif."                                     │
├─────────────────────────┼───────────────────────────────┼──────────────────────┼────────────────────────────────────────────────────────┤
│ memberRevisionRequested │ User Anggota ($member->user)  │ Warning              │ "Data keanggotaan perlu diperbaiki: Silakan periksa    │
│                         │                               │                      │ catatan revisi dan lengkapi kembali data Anda."        │
├─────────────────────────┼───────────────────────────────┼──────────────────────┼────────────────────────────────────────────────────────┤
│ memberRejected          │ User Anggota ($member->user)  │ Warning              │ "Pengajuan keanggotaan ditolak: Silakan hubungi        │
│                         │                               │                      │ koperasi untuk informasi lanjutan."                    │
└─────────────────────────┴───────────────────────────────┴──────────────────────┴────────────────────────────────────────────────────────┘
```

*Catatan Saluran Pengiriman*: Saat ini notifikasi tersimpan pada database notifikasi in-app aplikasi. Penambahan saluran pengiriman eksternal (gateway WhatsApp atau Email transaksional) merupakan keputusan konfigurasi operasional terpisah.

---

## 18. Kontrak Serah Terima untuk ONB-04 (ONB-04 Handoff Contract)

Modul `ONB-04 (Backend Import Validator)` memegang kepemilikan penuh atas parsing dan resolusi referensi sebelum data dipersist ke database kanonikal:

```text
       [ Berkas Batch CSV 12-Kolom ]
                     │
                     ▼
        [ ONB-04 Backend Validator ]
   - Validasi struktur berkas & format data baris
   - Validasi nilai terkontrol (gender L/P, membership_type AB/ALB)
   - Deteksi konflik NIK/Email/NoAnggota di tingkat staging
   - Resolusi referensi NIP vs employees.employee_code (Pra-Persistensi)
                     │
        ┌────────────┴──────────────────────────┐
        │                                       │
        ▼                                       ▼
 [ Resolusi NIP Sukses / Blank ]       [ NIP Diisi Tapi Unresolved ]
        │                                       │
        ▼                                       ▼
  [ Persist ke cooperative_members ]      [ FAIL-CLOSED MUTLAK ]
   - status            = 'PENDING'         - DILARANG PERSIST ke cooperative_members
   - validation_status = 'PENDING'         - Tertahan di antrean staging/manual-review ONB-04
   - employee_id       = ID ter-resolve    - Setelah dikoreksi HR & ter-resolve, barulah
                         atau NULL (murni    divalidasi ulang untuk dipersist.
                         non-karyawan)
   - admin_validated_at= NULL
   - validated_at      = NULL
                     │
                     ▼
[ ONB-03 Alur Kerja Verifikasi Admin Mengambil Alih ]
   - Admin Koperasi meninjau antrean status PENDING
   - Admin memverifikasi -> status PENDING_VALIDATION
   - Pengurus Koperasi memberikan persetujuan final -> ACTIVE
```

### Pernyataan Batas Otoritatif ONB-04 ke ONB-03:
1. **ONB-04 memiliki kepemilikan penuh resolusi referensi pegawai pra-persistensi**.
2. **ONB-03 hanya menerima record anggota yang telah dipersist**:
   - **Kasus A**: Tanpa referensi pegawai (pelamar non-karyawan), dengan `employee_id = NULL`.
   - **Kasus B**: Referensi pegawai berhasil ter-resolve secara unik, dengan `employee_id` terisi ID pegawai yang sah.
3. Seluruh baris dengan referensi pegawai tak ter-resolve **DILARANG** dipersist ke tabel `cooperative_members` dan **DILARANG** diubah secara diam-diam menjadi `employee_id = NULL`.

---

## 19. Klasifikasi Celah & Temuan (Gaps Found)

### Category A: Telah Terimplementasi Penuh & Tervalidasi (Already Fully Implemented)
1. **Pemisahan Status Operasional & Validasi**: Model `CooperativeMember` memiliki konstanta dan kolom yang lengkap untuk membedakan status aktif operasional dengan status proses validasi.
2. **Mesin Transisi Berkeamanan Tinggi**: `MemberStatusTransitionService` memiliki row locking atomik, validasi izin berbasis Spatie, dan pencatatan audit log otomatis.
3. **Pencabutan Akses Terpadu**: `MemberAccessRevocationService` menjamin pencabutan role `Anggota` dan penarikan token Sanctum saat revisi/penolakan.
4. **Validasi Otorisasi Web Controller**: Controller `CooperativeMemberValidationController` memeriksa hak akses dan visibilitas multi-organisasi (`OrganizationScopeService`).

### Category B: Terimplementasi namun Memerlukan Pengujian Tambahan (Needs Test / Docs)
1. **Pengujian Penegakan Maker-Checker**: Logika penolakan `assertApproverIsNotVerifier` telah ada pada service, namun sebelumnya belum diuji secara spesifik dalam suite pengujian fitur integrasi saat approver mencoba menyetujui anggota yang diverifikasinya sendiri.
2. **Pengujian Mutasi System Admin terhadap Maker-Checker**: Memastikan peran `System Admin` tidak dapat mengabaikan batasan Maker-Checker.
3. **Pengujian Keberadaan Jejak Audit**: Memverifikasi entri audit log tersimpan pada database setelah aksi verifikasi dan persetujuan final.

### Category C: Penyesuaian Kode Kecil yang Diselesaikan pada ONB-03 / ONB-03R1 (Implemented in Code)
1. **Penyelarasan Visibilitas Aksi UI pada `Show.vue` (Fix 1)**:
   - Mengubah kondisi dropdown menu "Minta revisi" dan "Tolak anggota" dari `canReviewMember && (isAdminVerificationReady || isFinalApprovalReady)` menjadi `canReviewMember && isFinalApprovalReady`.
   - Menghilangkan anomali UI di mana tombol aksi penolakan/revisi muncul pada state `PENDING` yang tidak didukung oleh backend lifecycle.
2. **Penambahan Test Regression Maker-Checker & Audit**:
   - Menambahkan `test_same_actor_cannot_final_approve_member_they_verified` pada `tests/Feature/Cooperative/CooperativeMemberValidationTest.php`.
   - Menambahkan `test_system_admin_cannot_final_approve_member_they_verified` pada `tests/Feature/Cooperative/CooperativeMemberValidationTest.php`.
   - Menambahkan `test_admin_verification_and_approval_produces_audit_logs` pada `tests/Feature/Cooperative/CooperativeMemberValidationTest.php`.
   - Seluruh 13 pengujian fitur validasi anggota dinyatakan **PASS (49 assertions)**.

### Category D: Di Luar Ruang Lingkup / Memerlukan Keputusan Arsitektur Tingkat Lanjut (Out of Scope)
1. **Penanganan Form Input Karyawan Sebelum Master HR Ada**: Dikelola secara fail-closed pada antrean validasi pre-persistence ONB-04 tanpa mengubah skema tabel database `cooperative_members`.
2. **Gateway Komunikasi Eksternal**: Pemilihan vendor penyedia WhatsApp API atau SMTP transaksional untuk notifikasi anggota di luar aplikasi.

---

## 20. Keputusan Operasional Terbuka (Open Operational Decisions)

Dua keputusan operasional bisnis murni yang tidak memblokir penyelesaian spesifikasi teknis:

1. **Batas Waktu (*SLA*) Tanggapan Revisi Calon Anggota**:
   - Berapa lama permohonan keanggotaan berstatus `REVISION` dipertahankan dalam antrean sebelum dianggap kedaluwarsa atau ditolak otomatis oleh sistem (misalnya 14 hari kerja atau 30 hari kalender).
2. **Saluran Notifikasi Publik Utama Pelamar**:
   - Mengingat pelamar yang berstatus `PENDING` atau `REVISION` belum dapat mengakses dashboard mobile/web anggota secara penuh, apakah notifikasi panggilan revisi dikirimkan via email pendaftaran atau notifikasi pesan instan WhatsApp ke nomor pelamar.
