# Improve 3 - Audit Lanjutan Aplikasi Kojaya

Tanggal audit: 6 Mei 2026  
Basis audit: `docs/project.md`, `docs/architecture.md`, `docs/improve2.md`, `docs/proses_bisnis/roles.md`, `docs/proses_bisnis/plan_role.md`, `routes/web.php`, `routes/api.php`, controller, service, halaman Inertia, dan test feature yang tersedia.

## Ringkasan Eksekutif

Aplikasi sudah berkembang dari ERP internal menjadi platform operasional yang cukup luas: HR, payroll, finance, procurement, asset maintenance, koperasi, POS, member portal, ESS, technician API, payment foundation, push token foundation, dan OpenAPI dasar. Setelah pekerjaan Phase 0-5 di `docs/improve2.md`, gap terbesar bukan lagi "fitur belum ada", tetapi "fitur belum cukup operasional untuk dipakai harian oleh role yang berbeda".

Prioritas berikutnya sebaiknya diarahkan ke:

1. Membuat UI operator untuk endpoint hardening yang sudah ada.
2. Menyatukan role matrix dan dokumentasi permission agar tidak drift.
3. Memperkuat segregation of duties untuk transaksi sensitif.
4. Mengubah fondasi integrasi internal menjadi integrasi produksi yang idempotent, terverifikasi, dan observable.
5. Menjaga kualitas engineering melalui contract test, API schemas, browser smoke test, dan quality gate CI.

## Penilaian Cepat

| Area | Status Saat Ini | Kekurangan Utama | Prioritas |
|---|---|---|---|
| Anggota koperasi | MVP web dan API mobile sudah cukup kuat | Journey status, receipt, gateway nyata, dokumen pinjaman, dan support follow-up belum matang | Tinggi |
| Pegawai ESS | API dan web portal tersedia | Mobile UX produksi, privacy payslip, approval koreksi absensi, dan push notification nyata belum lengkap | Tinggi |
| Teknisi lapangan | API technician sudah lengkap untuk workflow dasar | UI supervisor untuk review evidence, timeline, parts, reopen, dan SLA belum matang | Sedang |
| Operator koperasi | Endpoint approval inbox, closing, exception, reconciliation sudah ada | Belum ada halaman Inertia khusus untuk pekerjaan harian operator | Tinggi |
| Role dan permission | Spatie permission dan Sanctum abilities sudah digunakan | Dokumentasi role lama masih stale dan belum ada role matrix otomatis | Kritis |
| Integrasi produksi | Payment/push/OpenAPI/monitoring foundation ada | Provider payment dan push masih internal/basic, OpenAPI belum schema-rich | Tinggi |
| Engineering quality | Test feature banyak dan semakin baik | Belum ada e2e role smoke, contract API test, observability, dan CI gate lengkap | Tinggi |

## Temuan Dari Sisi Penggunaan Aplikasi

### 1. Operator membutuhkan cockpit kerja harian

Endpoint operator sudah tersedia di `Cooperative\OperatorProcedureController`: approval inbox, exceptions, analytics, closing checklist, lock/unlock period, reconciliation, dan export. Namun di `resources/js/pages` belum terlihat halaman Inertia khusus untuk cockpit operator tersebut.

Dampaknya, operator masih harus berpindah antar halaman modul atau memakai endpoint JSON langsung. Untuk penggunaan harian, ini membuat pekerjaan approval, closing, dan pengecekan anomali tidak cukup natural.

Saran:

- Buat halaman `Cooperative/Operator/Dashboard.vue` yang menampilkan pending payment, pending loan, pending redemption, payroll approval, overdue installment, unpaid dues, pending payment, dan low stock.
- Tambahkan halaman `Cooperative/Operator/Closing.vue` untuk checklist tutup periode, lock/unlock, dan status periode.
- Tambahkan tombol aksi langsung dari inbox menuju detail payment, loan, redemption, atau payroll.
- Tambahkan menu sidebar khusus "Operator Koperasi" dengan permission `view_cooperative_report` dan `manage_cooperative_settings`.

### 2. Anggota butuh status journey yang lebih jelas

Kojayaku web dan API member sudah punya dashboard, profile, savings, invoices, payments, loan, SHU, notifications, reward, dan support ticket. Kebutuhan dasar anggota sudah cukup, tetapi belum terlihat sebagai journey yang utuh.

Saran:

- Untuk pembayaran: tampilkan status `menunggu pembayaran -> menunggu verifikasi -> lunas/ditolak`, receipt digital, dan riwayat bukti.
- Untuk pinjaman: tampilkan timeline pengajuan, review, approval, pencairan, jadwal angsuran, overdue, dan alasan penolakan.
- Untuk reward: tampilkan status penukaran sampai fulfilled/cancelled.
- Untuk profil: pisahkan data yang bisa diedit langsung dan data yang butuh verifikasi operator, seperti KYC, rekening bank, NIK, dan dokumen.
- Untuk support ticket: tambahkan status, prioritas, balasan operator, dan SLA sederhana.

### 3. ESS pegawai sudah kuat untuk MVP, tetapi belum cukup untuk mobile produksi

API ESS sudah mencakup attendance, geofence, shift roster, leave, overtime, reimbursement, payslip, compliance, dan notifications. Gap berikutnya ada di kontrol lapangan dan privasi.

Saran:

- Tambahkan workflow koreksi absensi dengan approval atasan/HR.
- Tambahkan flag audit untuk mock location/rooted device dari mobile.
- Tambahkan watermark/download log untuk payslip.
- Tambahkan push notification nyata untuk approval/reject leave, overtime, reimbursement, payslip, dan compliance expiry.
- Tambahkan halaman admin untuk melihat anomali absensi mobile: geofence miss, akurasi GPS buruk, device berubah, dan check-in di luar shift.

### 4. Teknisi butuh review UI untuk supervisor

API technician sudah mendukung work order list/detail, start, complete, checklist, attachment, parts, sync, timeline, escalate, dan reopen. Dari sisi backend ini cukup, tetapi supervisor perlu layar review.

Saran:

- Tambahkan halaman supervisor untuk review timeline, evidence foto, spare part usage, GPS completion, dan checklist completion.
- Tambahkan SLA status: belum mulai, on progress, escalated, terlambat, selesai, reopened.
- Tambahkan re-open reason wajib dan audit trail yang mudah dibaca.
- Tambahkan ringkasan material/spare part per work order untuk finance atau inventory.

### 5. Procurement sudah ada, tetapi perlu SOP end-to-end yang lebih eksplisit

Procurement memiliki PR, PO, GRN, vendor, dan test web flow. Gap yang perlu diperkuat dari sisi pengguna adalah keterhubungan prosedural antar dokumen.

Saran:

- Tampilkan breadcrumb bisnis PR -> approval -> PO -> GRN -> invoice/payment.
- Tambahkan status dokumen yang konsisten dan mudah dipahami.
- Tambahkan indikator siapa approval berikutnya dan berapa lama dokumen menunggu.
- Tambahkan exception untuk PR tanpa PO, PO overdue, GRN partial, dan vendor bermasalah.

## Temuan Dari Sisi Role dan Prosedur

### 1. Dokumentasi role perlu menjadi source of truth, bukan snapshot manual

`PermissionEnum`, seeder role, policy, controller authorization, dan sidebar sudah jauh lebih matang. Namun `docs/proses_bisnis/roles.md` masih memuat analisis lama yang sudah tidak sepenuhnya sesuai dengan implementasi terbaru.

Risiko:

- Tim developer baru bisa mengambil keputusan dari dokumen yang stale.
- QA sulit membedakan gap nyata dan gap historis.
- Audit role menjadi manual dan rawan salah.

Saran:

- Buat command atau test yang menghasilkan role matrix dari `RolePermissionSeeder` dan `PermissionEnum`.
- Update `docs/proses_bisnis/roles.md` menjadi dokumen prosedur role, bukan daftar manual yang mudah stale.
- Tambahkan test yang memastikan semua permission di sidebar ada di enum/seeder.
- Tambahkan test yang memastikan setiap route sensitif punya middleware ability, policy, atau controller authorization.

### 2. Spatie permission dan Sanctum abilities perlu kontrak mapping

Web memakai Spatie permissions, sedangkan API mobile memakai Sanctum token abilities. Ini desain yang masuk akal karena web admin dan mobile persona punya kebutuhan berbeda. Namun mapping-nya perlu dibuat eksplisit.

Saran:

- Dokumentasikan mapping role -> Sanctum abilities di `docs/api.md` atau dokumen role.
- Tambahkan test untuk `AuthController` agar role Anggota, Employee, Technician, Pengurus Koperasi, Kasir Koperasi, Admin Pusat, dan System Admin selalu mendapat ability yang benar.
- Hindari ability terlalu luas seperti `member:write` untuk operasi payment charge tanpa ownership check. Saat ini sudah ada ownership check di controller, tetapi pola ini harus dijadikan aturan.

### 3. Segregation of duties belum terlihat sebagai aturan umum

Untuk koperasi, finance, payroll, procurement, dan bank reconciliation, aplikasi perlu pemisahan tugas. Contoh: pembuat transaksi tidak boleh menjadi approver final; kasir tidak otomatis boleh reconcile; admin user tidak otomatis boleh export audit.

Saran:

- Tambahkan rule creator cannot approve untuk transaksi sensitif: loan, payment reconciliation, payroll approval, reimbursement, PR/PO, closing period.
- Pisahkan role operasional baru bila diperlukan: `Reconciliation Officer`, `Closing Supervisor`, `Loan Committee`, `Cooperative Auditor`, `Integration Admin`.
- Tambahkan approval log standar berisi actor, permission used, previous status, next status, reason, IP/device, dan timestamp.
- Tambahkan test negatif: user dengan permission create tidak otomatis bisa approve.

### 4. Permission UI belum cukup sampai level tombol aksi

`AppSidebar.vue` sudah memfilter menu berdasarkan permission. Ini bagus, tetapi tombol aksi di halaman detail/list juga harus konsisten.

Saran:

- Terapkan helper permission atau directive `v-can` pada tombol create/edit/delete/approve/export/reconcile/lock.
- Pastikan tombol tersembunyi bukan satu-satunya keamanan; backend tetap menjadi enforcement utama.
- Tambahkan browser atau component smoke test untuk beberapa persona utama: Anggota, Employee, Technician, Kasir Koperasi, Pengurus Koperasi, Finance, HR, System Admin.

## Temuan Dari Sisi Senior Software Engineer

### 1. Controller dan service domain mulai besar

Beberapa area sudah cukup kompleks: cooperative loan/payment/closing, ESS, technician, procurement, finance, payroll. Jika terus ditambah tanpa batas domain yang jelas, regresi akan lebih sulit dilacak.

Saran:

- Pecah workflow kompleks menjadi action class atau domain service kecil, misalnya `ApproveLoanAction`, `ReconcileCooperativePaymentAction`, `LockCooperativePeriodAction`.
- Pastikan setiap action memiliki transaksi database yang jelas.
- Gunakan event/outbox untuk side effect seperti notification, receipt, webhook, dan audit.

### 2. Integrasi payment masih foundation internal

`PaymentGatewayService` sudah menyiapkan charge dan webhook, tetapi provider masih `internal`, webhook belum terlihat memakai signature verification, idempotency key, retry policy, dan status transition guard yang kuat.

Saran:

- Buat interface provider: `PaymentGatewayProvider`.
- Tambahkan adapter Midtrans/Xendit/QRIS sesuai vendor yang dipilih.
- Verifikasi signature webhook dan simpan raw payload.
- Tambahkan idempotency berdasarkan gateway reference + event id.
- Batasi transisi status, misalnya `PENDING -> PAID/EXPIRED/FAILED`, dan jangan biarkan webhook lama mengubah status final sembarangan.
- Jadikan posting ledger, receipt, dan notification sebagai proses idempotent.

### 3. OpenAPI masih terlalu dasar untuk contract mobile

`OpenApiGenerator` baru menghasilkan path, operationId, dan response umum. Ini berguna sebagai awal, tetapi belum cukup untuk mobile developer.

Saran:

- Tambahkan security scheme Sanctum bearer token.
- Tambahkan request body schema, response schema, error schema, pagination schema, dan examples.
- Tandai endpoint berdasarkan persona: member, ess, technician, cooperative operator, admin.
- Tambahkan test snapshot untuk mencegah kontrak API berubah tanpa sadar.

### 4. Observability belum cukup untuk operasi produksi

Monitoring endpoint sudah ada, tetapi aplikasi operasional membutuhkan jejak yang bisa dipakai saat insiden: payment stuck, closing gagal, payroll salah, approval tidak muncul, dan push notification tidak terkirim.

Saran:

- Tambahkan structured logging dengan correlation id untuk request API mobile, payment webhook, closing, reconciliation, payroll, dan procurement approval.
- Tambahkan metric sederhana: pending approval count, failed webhook count, failed push count, overdue loan ratio, queue failure, slow endpoint.
- Tambahkan dashboard internal health yang membedakan aplikasi hidup, database hidup, queue hidup, storage hidup, dan integrasi vendor hidup.
- Tambahkan alert untuk failed jobs dan webhook retries.

### 5. Test suite sudah baik, tetapi perlu coverage lintas persona

Test feature sudah banyak, termasuk `Phase0MobileApiTest`, `Phase1MemberSelfServiceApiTest`, `Phase2EssMobileApiTest`, `Phase3TechnicianMobileApiTest`, `Phase4ControllerAuthorizationTest`, dan `Phase4Phase5OperatorHardeningTest`. Gap berikutnya adalah coverage lintas role dan contract.

Saran:

- Tambahkan role smoke test matrix: setiap role login, melihat menu yang benar, dan ditolak dari route yang bukan miliknya.
- Tambahkan test untuk seluruh route sensitif agar minimal satu unauthorized user mendapat 403.
- Tambahkan API contract test berbasis OpenAPI untuk mobile.
- Tambahkan concurrency test untuk period lock, payment reconciliation, payroll approval, dan POS stock.
- Tambahkan browser smoke test untuk halaman utama Inertia: dashboard, procurement, cooperative, finance, ESS, member portal.

### 6. Data governance perlu diperjelas

Aplikasi menyimpan data sensitif: NIK, payroll, payslip, KYC, rekening, dokumen compliance, medical checkup, dan audit log.

Saran:

- Tentukan field yang wajib encrypted at rest.
- Batasi akses payslip dan medical data dengan permission yang lebih granular.
- Tambahkan retention policy untuk device token, audit log, attachment, dan dokumen lama.
- Tambahkan download audit untuk payslip, KYC, certificate, medical checkup, dan financial report.
- Tambahkan backup/restore runbook dan test restore berkala.

## Roadmap Rekomendasi

### Phase A - Operasionalisasi UI dan Role Matrix

Target waktu: 1-2 minggu.

- Buat cockpit operator koperasi untuk approval inbox, exception dashboard, closing checklist, dan reconciliation.
- Tambahkan menu sidebar operator.
- Terapkan permission guard pada tombol aksi penting.
- Regenerasi dan update dokumentasi role matrix.
- Tambahkan role smoke test untuk menu dan route sensitif.

Definition of Done:

- Operator bisa menyelesaikan pekerjaan harian tanpa membuka endpoint JSON.
- `roles.md` sesuai dengan enum/seeder terbaru.
- Setiap role utama punya minimal satu test akses positif dan negatif.

### Phase B - Integrasi Produksi dan Contract API

Target waktu: 2-4 minggu.

Status tindak lanjut 6 Mei 2026: Phase B sudah diperbaiki pada area kontrak kritis. Payment webhook kini memverifikasi signature Midtrans dari `signature_key`, duplicate paid webhook tidak memicu reconcile/notifikasi ulang, fallback tanpa kredensial gateway kembali memakai provider internal, FCM memakai payload endpoint legacy yang sesuai konfigurasi, dan OpenAPI sudah memuat schema request untuk payment charge, payment webhook, serta push-token registration.

- Implementasi provider payment nyata.
- Verifikasi signature webhook, idempotency, retry, dan status transition.
- Implementasi push provider nyata, minimal FCM untuk Android.
- Perkaya OpenAPI dengan schema dan security.
- Tambahkan contract test untuk endpoint mobile.

Definition of Done:

- Payment charge dan webhook bisa diuji end-to-end di sandbox vendor.
- Mobile developer dapat memakai `openapi.json` tanpa membaca source controller.
- Push notification terkirim dan kegagalannya tercatat.

### Phase C - Workflow Approval dan Closing Lintas Modul

Target waktu: 1-2 bulan.

- Standarkan approval log untuk koperasi, finance, payroll, procurement, reimbursement, dan HR.
- Terapkan segregation of duties untuk transaksi sensitif.
- Tambahkan closing dashboard untuk koperasi dan finance.
- Tambahkan exception report lintas modul: overdue loan, unpaid dues, PR/PO overdue, payroll pending, reimbursement pending, bank unreconciled.

Definition of Done:

- Setiap transaksi finansial penting punya actor, reason, status history, dan audit trail.
- Creator tidak bisa menjadi approver final pada workflow sensitif.
- Closing period mencegah posting transaksi terlambat dengan pesan error yang jelas.

### Phase D - Production Reliability dan Governance

Target waktu: 2-3 bulan.

- Tambahkan structured logs, metrics, health checks, dan alert.
- Tambahkan e2e browser smoke untuk route utama.
- Tambahkan privacy hardening untuk payslip, KYC, medical checkup, dan document download.
- Tambahkan backup/restore runbook.
- Tambahkan CI gate untuk Pint, PHPUnit subset, frontend build, route generation, dan OpenAPI drift.

Definition of Done:

- Insiden payment/approval/notification bisa ditelusuri dari log dan metric.
- Perubahan route/API tidak lolos tanpa update contract.
- Data sensitif punya aturan akses, audit, retention, dan backup yang jelas.

## Prioritas Paling Dekat

1. Buat UI operator koperasi di atas endpoint Phase 4 yang sudah ada.
2. Rapikan `docs/proses_bisnis/roles.md` agar tidak bertentangan dengan implementasi permission terbaru.
3. Tambahkan permission guard pada tombol aksi sensitif, bukan hanya sidebar.
4. Perkuat payment gateway dengan signature, idempotency, dan status transition.
5. Perkaya OpenAPI agar benar-benar bisa dipakai tim mobile.
6. Tambahkan role smoke test dan unauthorized route matrix untuk modul sensitif.

## Kesimpulan

Kojaya sudah cukup kuat sebagai ERP internal dan sudah mulai siap menjadi platform multi-persona: admin web, anggota koperasi, pegawai ESS, teknisi, kasir, operator, dan pengurus. Tahap berikutnya harus mengubah kemampuan teknis yang sudah ada menjadi pengalaman operasional yang jelas: siapa mengerjakan apa, di layar mana, dengan izin apa, bukti apa, dan audit trail apa.

Roadmap paling bernilai adalah membangun UI operator, merapikan role matrix, memperkeras integrasi produksi, dan menambah quality gate. Dengan urutan itu, aplikasi tidak hanya punya banyak fitur, tetapi juga lebih aman, dapat diaudit, dan nyaman dipakai harian.
