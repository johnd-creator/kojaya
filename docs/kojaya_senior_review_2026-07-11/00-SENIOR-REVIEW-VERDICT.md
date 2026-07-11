# Senior Review Verdict — Kojaya Backend Member/Admin Hardening

**Tanggal:** 11 Juli 2026  
**Repository:** `johnd-creator/kojaya`  
**PR:** `#2`  
**Branch:** `audit/member-admin-role-review-2026-07-10`  
**Head yang direview:** `b89f04bd4a709de4e1ee9fa8bfc7eb2bc207b895`

## Keputusan

**REQUEST CHANGES — tetap Draft.**

Pekerjaan yang sudah dilakukan merupakan peningkatan nyata dan cukup besar, tetapi **belum layak dinyatakan P0/P1 selesai**, belum layak dijadikan PR final P2, dan belum layak digabung sebagai satu paket besar ke `main`.

Klasifikasi yang tepat saat ini:

> **Security hardening work-in-progress: P0/P1 incomplete, P2 partial.**

## Hal yang sudah membaik

1. Role `Anggota` sudah dipisahkan dari permission administrasi koperasi.
2. Export anggota mempunyai permission khusus dan organization filter.
3. API member utama sudah mempunyai active-member gate.
4. Wildcard token admin pada aplikasi mobile sudah dihilangkan.
5. Lifecycle tertentu sudah mencabut token Sanctum.
6. Maker-checker verifikasi anggota mulai ditegakkan.
7. Update profil dan provisioning mulai dibungkus transaksi.
8. Store/coffee order mulai memakai client reference, unique constraint, dan reservation.
9. API member admin mulai memakai resource allowlist dan masking.
10. Audit log mulai mempunyai correlation ID, organization, actor roles, reason, dan timestamp.
11. Seeder production tidak lagi membuat akun admin dengan password default.
12. Batas pagination mulai distandardisasi pada sebagian endpoint.

Perubahan tersebut layak dipertahankan, tetapi belum cukup untuk menutup roadmap.

## Blocker baru dan blocker yang belum selesai

| ID | Severity | Area | Temuan |
|---|---|---|---|
| SR-P0-01 | Critical | Active-member gate | Endpoint `/api/payments/charge`, `/api/v1/points/*`, dan `/api/v1/rewards/*` berada di luar `member.api.active`; controller juga hanya mengecek relasi anggota, bukan status aktif. |
| SR-P0-02 | Critical | Lifecycle web | Resign/deactivate hanya mengubah `status`, sedangkan middleware web memprioritaskan `validation_status`. Anggota yang sebelumnya aktif dapat tetap lolos sebagai fully active selama session web masih hidup. |
| SR-P0-03 | Critical/High | Multi-organization | Organization scope baru diterapkan pada beberapa list/stat/export. Direct route-model binding, policy, API member, resignation processing, dan loan masih memungkinkan akses lintas organisasi bagi role manage. |
| SR-P0-04 | High | Member-user linking | Provisioning dapat menemukan user global berdasarkan email lalu menambahkan role `Anggota` dan menautkannya ke member tanpa verifikasi identitas, organization, atau larangan terhadap privileged user. |
| SR-P0-05 | High | CI | GitHub Actions gagal pada Wayfinder drift; PHPUnit dan pemeriksaan berikutnya tidak berjalan. Tidak ada bukti CI penuh hijau untuk head terakhir. |
| SR-P1-01 | High | Token revocation | Revocation menghapus seluruh token user, termasuk kemungkinan token ESS/technician, bukan hanya token aplikasi anggota. |
| SR-P1-02 | High | State transition | Jalur `activate()` tidak menjamin `status` dan `validation_status` sama-sama `ACTIVE`, sehingga state dapat kontradiktif. |
| SR-P1-03 | High | Reservation lifecycle | Release reservation belum atomik terhadap intent, tidak ada expiry sweeper, charge lama dapat digunakan ulang, dan expiry order 30 menit dapat ditimpa menjadi 1 hari. |
| SR-P1-04 | Medium/High | Sanctum abilities | Resolver menghasilkan abilities granular, tetapi route admin masih menegakkan `cooperative:read/write`; migrasi domain ability belum selesai. |
| SR-P1-05 | High | Inertia PII | Web member index/show/edit masih mengirim model Eloquent mentah beserta relasi sensitif. Daftar user/employee pada create/edit juga belum di-scope dan belum di-allowlist. |
| SR-P1-06 | Medium/High | Audit | Audit contract belum konsisten, export belum diaudit, banyak direct `AuditLog::create`, dan PII masih dapat tersimpan plaintext di audit values. |
| SR-P1-07 | Medium | QueryException | Controller order menangkap semua `QueryException` dan menganggapnya duplicate idempotency key; kegagalan DB lain dapat tertutup. |
| SR-P2-01 | Medium | Encryption | Encryption at rest dan blind index belum diterapkan. |
| SR-P2-02 | Medium | Pagination | Batas pagination belum diterapkan ke seluruh API, termasuk ESS, technician, serta endpoint lama lainnya. |
| SR-P2-03 | Medium | Concurrency testing | Belum ada test dua transaksi paralel yang membuktikan reservation mencegah overselling dan double-release. |

## Status terhadap klaim Codex

### “P0/P1 aman untuk direview”

**Sebagian benar, tetapi tidak dapat disetujui sebagai selesai.**

Komponen P0/P1 tertentu sudah memiliki implementasi awal yang baik. Namun SR-P0-01 sampai SR-P0-05 masih harus ditutup sebelum status P0/P1 dapat dianggap selesai.

### “Boleh menjadi PR draft P2 partial hardening”

**Benar hanya jika PR tetap draft dan judul/body diubah.**

PR sekarang bukan lagi PR dokumentasi. Scope-nya sudah mencakup puluhan file production dan ribuan baris perubahan. Judul serta body harus mencerminkan bahwa ini integration branch untuk hardening, bukan report-only PR.

### “Belum layak P2 final”

**Setuju.**

Selain daftar P2 dari Codex, senior review menemukan beberapa masalah P0/P1 yang harus didahulukan.

## Merge recommendation

Jangan merge PR besar ini apa adanya.

Pilihan yang disarankan:

1. Jadikan branch sekarang sebagai **integration/reference branch**.
2. Pecah perubahan menjadi PR kecil berdasarkan domain.
3. Setiap PR wajib mempunyai:
   - scope tunggal;
   - negative authorization tests;
   - migration rollback test bila relevan;
   - CI penuh hijau;
   - tidak ada Wayfinder/OpenAPI drift;
   - senior review sebelum merge.

## Definition of Done untuk menyatakan roadmap selesai

Roadmap hanya boleh ditutup bila seluruh kondisi berikut terpenuhi:

- seluruh member write/financial endpoint mempunyai active gate yang konsisten;
- web dan API memakai satu state machine anggota;
- resign/deactivate/reject/revision menutup akses web serta API;
- direct object access lintas organisasi ditolak;
- member-user linking tidak dapat mengambil alih privileged user;
- revocation tidak mematikan token aplikasi lain tanpa alasan;
- Inertia dan API memakai explicit DTO/resource allowlist;
- PII terenkripsi dan searchable lewat blind index bila dibutuhkan;
- export/full PII view mempunyai audit event lengkap;
- reservation mempunyai expiry cleanup dan concurrency tests;
- seluruh API pagination bounded;
- CI penuh hijau dari Pint, frontend build, Wayfinder, PHPUnit, migration, dan OpenAPI drift checks.
