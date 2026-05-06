# Proses Bisnis Role Karyawan

## Ringkasan

Role karyawan menggunakan Employee Self Service (ESS) untuk menjalankan aktivitas mandiri terkait data kepegawaian: melihat dashboard, memperbarui profil dasar, absensi GPS, mengajukan cuti, mengajukan lembur, mengajukan reimbursement, melihat payslip, memantau dokumen compliance, dan menerima informasi terkait status pengajuan.

Karyawan hanya boleh mengakses data miliknya sendiri. Proses administratif seperti pembuatan data karyawan, aktivasi akses ESS, persetujuan cuti, approval lembur, approval reimbursement, payroll, mutasi, dan compliance tetap dikelola oleh HR, Finance, Admin Unit, atau Pengurus sesuai kewenangan di KojayaPro.

## Aktor Yang Terlibat

| Aktor | Peran |
|---|---|
| Karyawan | Menggunakan ESS untuk absensi, profil, cuti, lembur, reimbursement, payslip, dan compliance |
| HR Unit | Mengelola data karyawan, cuti, absensi, dokumen, dan administrasi unit |
| HR Pusat | Mengawasi kebijakan HR, payroll, compliance, dan data lintas unit |
| Finance Unit | Memverifikasi dan membayar reimbursement di unit |
| Finance Pusat | Mengawasi pembayaran dan laporan keuangan lintas unit |
| Atasan/Admin Unit | Menyetujui pengajuan operasional sesuai kewenangan |
| Sistem ESS | Kanal self-service karyawan di web atau mobile API |
| Sistem KojayaPro | Sistem administrasi HR, payroll, approval, compliance, dan laporan |

## Hak Akses Karyawan

Karyawan dapat:

- Login ke ESS jika akun sudah diaktifkan.
- Melihat dashboard ringkas kepegawaian.
- Melihat dan memperbarui profil dasar.
- Melakukan check-in dan check-out absensi.
- Melihat riwayat absensi.
- Mengajukan cuti dan melihat status cuti.
- Mengajukan lembur dan melihat status lembur.
- Mengajukan reimbursement dan melihat status pembayaran.
- Melihat daftar payslip miliknya.
- Melihat status dokumen sertifikat dan medical checkup.

Karyawan tidak dapat:

- Membuat atau mengubah data master karyawan sendiri.
- Mengubah status absensi yang sudah tercatat secara langsung.
- Menyetujui cuti, lembur, atau reimbursement sendiri.
- Mengubah nominal payroll atau payslip.
- Mengunggah atau mengubah dokumen compliance atas nama karyawan lain.
- Mengakses data karyawan lain.

## Proses 1: Pembuatan Data Karyawan dan Aktivasi ESS

### Tujuan

Membuat data karyawan valid dan memberikan akses ESS kepada karyawan yang berhak.

### Alur Utama

1. HR membuat data karyawan di KojayaPro.
2. HR mengisi data organisasi, departemen, posisi, kode karyawan, email, status, work shift, dan data payroll yang dibutuhkan.
3. HR memastikan karyawan memiliki email aktif.
4. HR menjalankan aktivasi akses ESS.
5. Sistem membuat user dengan nama karyawan, email, organisasi, dan password awal berdasarkan kode karyawan.
6. Sistem memberikan role Employee kepada user.
7. Sistem menghubungkan `employee.user_id` ke user yang dibuat.
8. Karyawan login ke ESS dengan kredensial awal.
9. Karyawan mengganti password sesuai kebijakan keamanan.

### Output

- Data karyawan aktif.
- Akun user ESS aktif.
- User terhubung ke data employee.

### Kondisi Gagal

- Email karyawan kosong.
- Email sudah digunakan user lain.
- Data karyawan belum aktif.
- User belum terhubung ke employee.

## Proses 2: Login dan Dashboard ESS

### Tujuan

Memberikan akses aman kepada karyawan untuk melihat ringkasan kondisi kepegawaiannya.

### Alur Utama

1. Karyawan memasukkan email dan password.
2. Sistem memvalidasi kredensial.
3. Untuk mobile API, sistem memakai token Sanctum dan ability ESS.
4. Sistem memeriksa apakah user memiliki profil employee.
5. Sistem menampilkan dashboard ESS.
6. Dashboard memuat data employee, departemen, posisi, organisasi, statistik absensi bulan berjalan, cuti pending, cuti approved tahun berjalan, payroll terbaru, sertifikat hampir expired, dan medical checkup yang jatuh tempo.

### Output

- Sesi web atau token mobile aktif.
- Dashboard ESS tampil.

### Kontrol Bisnis

- Jika akun belum terhubung ke employee, akses ditolak.
- Ringkasan dashboard harus dihitung dari employee yang sedang login.

## Proses 3: Pengelolaan Profil Karyawan

### Tujuan

Memungkinkan karyawan memperbarui data pribadi dasar yang tidak memerlukan approval berat.

### Alur Utama

1. Karyawan membuka menu Profil ESS.
2. Sistem menampilkan data user dan data employee.
3. Karyawan memperbarui nama, email, tanggal lahir, atau gender.
4. Sistem memvalidasi format data.
5. Sistem menyimpan perubahan ke user dan employee.
6. Sistem menampilkan konfirmasi perubahan berhasil.

### Output

- Data profil dasar diperbarui.

### Catatan Proses

Data sensitif seperti gaji, rekening bank, NPWP, BPJS, posisi, departemen, organisasi, dan status kerja sebaiknya tetap hanya bisa diubah oleh HR atau admin berwenang.

## Proses 4: Check-In Absensi

### Tujuan

Mencatat kehadiran karyawan pada hari kerja dengan validasi lokasi.

### Alur Utama Web ESS

1. Karyawan membuka halaman absensi self-service.
2. Sistem mengambil data employee, shift kerja, roster hari ini, dan absensi hari ini.
3. Karyawan menekan check-in.
4. Browser mengirim latitude dan longitude.
5. Sistem memvalidasi profil employee.
6. Sistem memeriksa geofence organisasi jika latitude, longitude, dan radius organisasi tersedia.
7. Jika karyawan memiliki shift group, sistem memeriksa roster hari ini.
8. Jika roster adalah hari libur, check-in ditolak.
9. Sistem memeriksa apakah karyawan sudah check-in hari ini.
10. Sistem menentukan shift efektif dan jam selesai terjadwal.
11. Sistem membuat atau memperbarui attendance hari ini dengan `clock_in`, status `PRESENT`, work shift, dan scheduled end time.
12. Sistem menampilkan konfirmasi check-in berhasil.

### Alur Utama Mobile API

1. Mobile app mengirim latitude, longitude, accuracy, dan device_id opsional.
2. Sistem memvalidasi token dan ability `attendance:write`.
3. Sistem memastikan user terhubung ke employee.
4. Sistem memvalidasi geofence organisasi.
5. Sistem menolak check-in jika accuracy GPS terlalu rendah.
6. Sistem menolak check-in jika lokasi di luar radius organisasi.
7. Sistem menolak check-in jika karyawan sudah check-in hari ini.
8. Sistem mencatat attendance dengan `clock_in`, status `PRESENT`, dan catatan device jika tersedia.

### Output

- Attendance hari ini tercatat.
- Status kehadiran menjadi PRESENT.

### Kondisi Gagal

- Akun tidak terhubung ke employee.
- Lokasi tidak dikirim padahal organisasi memakai geofence.
- GPS accuracy terlalu rendah.
- Karyawan berada di luar radius geofence.
- Hari ini adalah off day pada roster.
- Karyawan sudah check-in.

## Proses 5: Check-Out Absensi

### Tujuan

Mencatat waktu pulang dan menghitung indikasi lembur jika checkout melewati scheduled end time.

### Alur Utama Web ESS

1. Karyawan menekan check-out.
2. Sistem mencari attendance hari ini.
3. Sistem menolak jika karyawan belum check-in.
4. Sistem menolak jika karyawan sudah check-out.
5. Sistem mencatat `clock_out`.
6. Jika ada scheduled end time dan checkout melewati jadwal, sistem menandai `is_overtime` dan menghitung `overtime_hours`.
7. Sistem menyimpan attendance.
8. Sistem menampilkan konfirmasi check-out berhasil.

### Alur Utama Mobile API

1. Mobile app mengirim latitude, longitude, accuracy, dan device_id opsional.
2. Sistem memvalidasi token dan ability `attendance:write`.
3. Sistem memvalidasi employee dan geofence.
4. Sistem mencari attendance hari ini.
5. Sistem menolak jika belum check-in.
6. Sistem menolak jika sudah check-out.
7. Sistem menyimpan `clock_out`.

### Output

- Jam pulang tercatat.
- Indikasi lembur dapat terbentuk dari attendance web.

### Catatan Proses

Indikasi lembur dari attendance tidak otomatis berarti lembur dibayar. Pembayaran lembur tetap mengikuti proses pengajuan atau approval lembur sesuai aturan perusahaan.

## Proses 6: Riwayat Absensi

### Tujuan

Memberikan transparansi kepada karyawan atas catatan kehadirannya.

### Alur Utama

1. Karyawan membuka riwayat absensi.
2. Sistem mengambil attendance berdasarkan employee login.
3. Sistem menampilkan tanggal, clock-in, clock-out, status, shift, dan informasi lembur jika ada.
4. Karyawan dapat memeriksa data yang perlu dikoreksi.
5. Jika ada kesalahan, karyawan menghubungi HR atau menggunakan mekanisme koreksi jika sudah tersedia.

### Output

- Karyawan mengetahui riwayat absensi dan potensi anomali.

### Pengembangan Yang Disarankan

- Tambahkan pengajuan koreksi absensi dengan approval.
- Simpan latitude, longitude, accuracy, device_id, dan audit log terstruktur, bukan hanya catatan teks.

## Proses 7: Pengajuan Cuti

### Tujuan

Memungkinkan karyawan mengajukan cuti secara mandiri dan memantau statusnya.

### Alur Utama

1. Karyawan membuka menu cuti self-service.
2. Sistem menampilkan daftar cuti milik karyawan dan jenis cuti.
3. Karyawan memilih jenis cuti.
4. Karyawan mengisi tanggal mulai, tanggal selesai, alasan, dan lampiran jika diperlukan.
5. Sistem memvalidasi input.
6. Sistem memeriksa apakah jenis cuti wajib lampiran.
7. Sistem menghitung total hari kerja dengan melewati akhir pekan.
8. Sistem menyimpan lampiran jika ada.
9. Sistem membuat leave request dengan status `Pending`.
10. HR atau atasan membuka daftar cuti untuk approval.
11. HR atau atasan mengubah status menjadi `Approved` atau `Rejected`.
12. Karyawan melihat status terbaru di ESS.

### Output

- Pengajuan cuti tercatat.
- Status cuti dapat dipantau.

### Status Cuti

| Status | Makna Bisnis |
|---|---|
| Pending | Pengajuan menunggu approval |
| Approved | Cuti disetujui |
| Rejected | Cuti ditolak |

### Kontrol Bisnis

- Karyawan hanya membuat cuti untuk employee miliknya.
- Approval dilakukan oleh role berwenang.
- Jenis cuti yang wajib lampiran harus memiliki file pendukung.

## Proses 8: Pengajuan Lembur

### Tujuan

Mencatat permintaan lembur karyawan dan memastikan lembur mengikuti aturan organisasi.

### Alur Utama

1. Karyawan atau admin membuat overtime request.
2. Sistem menampilkan aturan lembur yang aktif.
3. Pengaju memilih karyawan, aturan lembur, tanggal, jam mulai, jam selesai, alasan, dan evidence opsional.
4. Sistem menghitung total jam lembur.
5. Sistem memvalidasi minimum jam dan maksimum jam harian dari overtime rule.
6. Sistem menyimpan evidence jika ada.
7. Jika rule memerlukan approval, status menjadi `PENDING`.
8. Jika rule tidak memerlukan approval, status menjadi `APPROVED`.
9. Atasan, HR, atau admin menyetujui atau menolak pengajuan.
10. Jika approved, lembur dapat masuk proses perhitungan payroll atau pembayaran lembur.

### Output

- Pengajuan lembur tercatat.
- Status lembur dapat dipantau.

### Status Lembur

| Status | Makna Bisnis |
|---|---|
| PENDING | Menunggu approval |
| APPROVED | Disetujui dan dapat diproses |
| REJECTED | Ditolak dengan alasan |

### Kontrol Bisnis

- Karyawan normal hanya boleh melihat pengajuan lemburnya sendiri.
- Admin unit atau HR unit hanya boleh melihat data unitnya.
- Request approved tidak boleh dihapus sembarangan.

## Proses 9: Pengajuan Reimbursement

### Tujuan

Memungkinkan karyawan mengajukan penggantian biaya operasional dan memantau pembayaran.

### Alur Utama

1. Karyawan membuka menu reimbursement.
2. Sistem menampilkan reimbursement milik karyawan.
3. Karyawan membuat reimbursement baru.
4. Karyawan mengisi tanggal pengajuan, deskripsi, dan daftar item.
5. Untuk setiap item, karyawan mengisi kategori, deskripsi, nominal, tanggal kuitansi, dan file struk jika ada.
6. Sistem menjumlahkan total amount dari semua item.
7. Sistem membuat reimbursement dengan status `SUBMITTED`.
8. HR Unit, Finance Unit, Admin Unit, atau role pusat memeriksa pengajuan.
9. Pengajuan dapat di-approve atau reject.
10. Jika rejected, sistem menyimpan alasan penolakan.
11. Jika approved, Finance Unit atau Finance Pusat menandai reimbursement sebagai `PAID`.
12. Karyawan melihat status pengajuan dan pembayaran.

### Output

- Reimbursement tercatat.
- Item dan file struk tersimpan.
- Status approval dan pembayaran dapat dipantau.

### Status Reimbursement

| Status | Makna Bisnis |
|---|---|
| SUBMITTED | Diajukan dan menunggu review |
| APPROVED | Disetujui dan menunggu pembayaran |
| REJECTED | Ditolak dengan alasan |
| PAID | Sudah dibayar oleh Finance |

### Kontrol Bisnis

- Karyawan hanya melihat reimbursement miliknya sendiri.
- Unit admin melihat data organisasinya.
- Finance hanya boleh menandai paid jika status sudah APPROVED.
- Reimbursement yang sudah PAID tidak perlu dibayar ulang.

## Proses 10: Payslip dan Payroll

### Tujuan

Memberikan transparansi kepada karyawan atas hasil payroll yang sudah diproses.

### Alur Utama

1. HR atau Finance memproses payroll periode tertentu di KojayaPro.
2. Sistem menghitung gaji, tunjangan, potongan, BPJS, PPh21, lembur, THR, dan komponen lain sesuai data payroll.
3. Payroll melewati proses approval internal jika diterapkan.
4. Setelah payroll tersedia, karyawan membuka menu Payslip.
5. Sistem menampilkan daftar payroll milik employee login, diurutkan dari periode terbaru.
6. Karyawan membuka detail atau mengunduh slip gaji jika fitur tersedia.

### Output

- Karyawan dapat melihat riwayat payslip miliknya.
- Payroll tetap dikontrol oleh HR dan Finance.

### Kontrol Bisnis

- Karyawan hanya boleh melihat payslip miliknya sendiri.
- Data payroll tidak boleh diubah oleh karyawan.
- Akses payslip sebaiknya memakai kontrol privasi tambahan seperti re-auth, PIN, atau biometrik pada mobile.

## Proses 11: Compliance Dokumen dan Medical Checkup

### Tujuan

Memastikan karyawan mengetahui status sertifikasi, dokumen wajib, dan medical checkup yang akan habis masa berlaku atau jatuh tempo.

### Alur Utama

1. HR mengelola data sertifikat karyawan dan medical checkup.
2. Sistem menyimpan tanggal berlaku, tanggal expired, status, dan file dokumen jika ada.
3. Karyawan membuka menu Compliance.
4. Sistem menampilkan sertifikat berdasarkan tanggal expired.
5. Sistem menampilkan riwayat medical checkup berdasarkan tanggal pemeriksaan.
6. Dashboard ESS menghitung sertifikat yang akan expired dalam 60 hari dan MCU yang jatuh tempo dalam 30 hari.
7. Karyawan menindaklanjuti pembaruan dokumen melalui HR.

### Output

- Karyawan mengetahui dokumen yang perlu diperbarui.
- HR dapat memantau risiko compliance.

### Kontrol Bisnis

- Pembuatan dan perubahan dokumen compliance dilakukan oleh role berwenang.
- Karyawan hanya melihat dokumen miliknya.

## Proses 12: Mutasi atau Transfer Karyawan

### Tujuan

Mengelola perpindahan organisasi, departemen, posisi, atau unit kerja karyawan.

### Alur Utama

1. HR atau admin membuat usulan transfer karyawan.
2. Sistem mencatat employee, organisasi atau posisi asal, tujuan, tanggal efektif, dan alasan.
3. Pengurus atau role berwenang menyetujui atau menolak transfer.
4. Jika approved, data employee diperbarui sesuai keputusan.
5. Karyawan melihat perubahan organisasi, departemen, atau posisi pada profil ESS.

### Output

- Transfer tercatat dan dapat diaudit.
- Data profil kerja karyawan diperbarui setelah approval.

### Kontrol Bisnis

- Karyawan tidak mengajukan atau menyetujui mutasi sendiri jika kebijakan perusahaan tidak mengizinkan.
- Perubahan unit kerja harus memengaruhi hak akses, laporan, dan scope data.

## Proses 13: Notifikasi Karyawan

### Tujuan

Memberi informasi kepada karyawan terkait aktivitas ESS dan status pengajuan.

### Alur Utama

1. Sistem atau operator membuat notifikasi.
2. Notifikasi dikirim ke user karyawan.
3. Karyawan membaca notifikasi di web atau mobile jika fitur tersedia.
4. Karyawan menindaklanjuti notifikasi sesuai konteks.

### Contoh Notifikasi

- Cuti disetujui atau ditolak.
- Lembur disetujui atau ditolak.
- Reimbursement disetujui, ditolak, atau dibayar.
- Payslip periode terbaru tersedia.
- Sertifikat akan expired.
- MCU akan jatuh tempo.

## Proses 14: Logout

### Tujuan

Mengakhiri sesi karyawan dengan aman.

### Alur Utama

1. Karyawan memilih logout.
2. Untuk web, sistem mengakhiri sesi.
3. Untuk mobile, sistem mencabut token saat ini.
4. Jika logout semua perangkat dipilih, sistem mencabut seluruh token user.
5. Karyawan harus login ulang untuk mengakses ESS.

## Ringkasan Menu Karyawan

| Menu | Fungsi Utama | Data Sumber |
|---|---|---|
| Dashboard ESS | Ringkasan absensi, cuti, payroll, compliance | Employee, attendance, leave, payroll, certificate, MCU |
| Profil | Data user dan data employee dasar | User, employee |
| Absensi | Check-in, check-out, absensi hari ini, riwayat | Attendance, organization, work shift, roster |
| Cuti | Pengajuan dan tracking cuti | Leave, leave type, approver |
| Lembur | Pengajuan dan tracking lembur | Overtime request, overtime rule |
| Reimbursement | Pengajuan biaya dan status bayar | Reimbursement, reimbursement item |
| Payslip | Riwayat slip gaji | Payroll, organization |
| Compliance | Sertifikat dan medical checkup | Employee certificate, medical checkup |
| Notifikasi | Informasi status dan pengingat | User notification |

## Integrasi Dengan KojayaPro

| Proses Karyawan | Proses HR/Finance/Admin |
|---|---|
| Aktivasi ESS | HR membuat user dan menghubungkan employee |
| Update profil dasar | HR memantau data sensitif jika perlu |
| Check-in/check-out | HR memonitor attendance dan koreksi |
| Pengajuan cuti | HR atau atasan approve/reject |
| Pengajuan lembur | Atasan atau HR approve/reject |
| Reimbursement | HR/Finance approve, Finance menandai paid |
| Payslip | HR/Finance memproses payroll |
| Compliance | HR mengelola sertifikat dan MCU |
| Mutasi | HR/Admin membuat transfer, pengurus approve/reject |

## Endpoint ESS Mobile

Endpoint mobile ESS berada di namespace `/api/ess` dan memakai autentikasi Sanctum.

| Endpoint | Fungsi |
|---|---|
| `GET /api/ess/dashboard` | Ringkasan dashboard karyawan |
| `GET /api/ess/profile` | Detail profil user dan employee |
| `PUT /api/ess/profile` | Update profil dasar |
| `GET /api/ess/attendance/today` | Attendance hari ini |
| `GET /api/ess/attendance/history` | Riwayat attendance |
| `POST /api/ess/attendance/check-in` | Check-in dengan lokasi |
| `POST /api/ess/attendance/check-out` | Check-out dengan lokasi |
| `GET /api/ess/geofence` | Koordinat dan radius geofence organisasi |

Endpoint yang direkomendasikan untuk melengkapi ESS mobile:

| Endpoint | Fungsi |
|---|---|
| `GET /api/ess/leaves` | Daftar cuti karyawan |
| `POST /api/ess/leaves` | Pengajuan cuti |
| `POST /api/ess/leaves/{leave}/cancel` | Pembatalan cuti pending |
| `GET /api/ess/overtime` | Daftar lembur karyawan |
| `POST /api/ess/overtime` | Pengajuan lembur |
| `GET /api/ess/reimbursements` | Daftar reimbursement karyawan |
| `POST /api/ess/reimbursements` | Pengajuan reimbursement |
| `GET /api/ess/payslips` | Daftar payslip |
| `GET /api/ess/payslips/{payroll}/download` | Download payslip |
| `GET /api/ess/compliance` | Sertifikat dan MCU |
| `GET /api/ess/notifications` | Notifikasi karyawan |

## Aturan Data dan Keamanan

- Semua data ESS harus difilter berdasarkan employee dari user login.
- API ESS harus memakai ability seperti `ess:read`, `ess:write`, `attendance:read`, dan `attendance:write`.
- Employee ID tidak boleh diambil dari input bebas untuk aksi self-service.
- Lokasi absensi harus divalidasi terhadap geofence organisasi jika konfigurasi tersedia.
- Pengajuan yang berdampak finansial harus melalui approval role berwenang.
- Data payroll dan payslip harus diperlakukan sebagai data sensitif.
- File lampiran cuti, lembur, reimbursement, dan compliance perlu validasi format, ukuran, dan akses.
- Semua perubahan penting sebaiknya menghasilkan audit trail.

## Risiko dan Gap Yang Perlu Ditutup

| Area | Gap | Rekomendasi |
|---|---|---|
| Absensi mobile | Device binding dan penyimpanan koordinat belum lengkap | Simpan latitude, longitude, accuracy, device_id, nonce, dan audit log |
| Koreksi absensi | Belum ada flow mandiri | Tambahkan request koreksi dengan approval HR |
| Cuti mobile | Endpoint cuti mobile belum lengkap | Tambahkan list, create, cancel, leave balance, dan attachment |
| Lembur mobile | Belum mobile-first | Tambahkan endpoint pengajuan dan tracking lembur |
| Reimbursement mobile | Upload struk mobile belum tersedia sebagai API ESS khusus | Tambahkan endpoint mobile dengan multipart upload |
| Payslip | Kontrol privasi mobile belum spesifik | Tambahkan re-auth, PIN, biometric, watermark, dan download aman |
| Compliance | Karyawan belum bisa upload pembaruan dokumen untuk review | Tambahkan upload dokumen dengan status pending approval |
| Notifikasi | Push notification produksi masih roadmap | Integrasikan FCM atau kanal notifikasi internal |

## Indikator Keberhasilan

- Karyawan dapat login ESS tanpa bantuan HR setelah akses diaktifkan.
- Karyawan dapat check-in dan check-out sesuai geofence.
- Karyawan dapat melihat riwayat absensi dan status kehadiran.
- Karyawan dapat mengajukan cuti dan memantau approval.
- Karyawan dapat mengajukan lembur dan reimbursement dengan bukti pendukung.
- Karyawan dapat melihat payslip miliknya secara aman.
- Karyawan mengetahui sertifikat dan MCU yang hampir expired atau jatuh tempo.
- HR, Finance, dan Admin dapat menindaklanjuti semua proses karyawan dari KojayaPro.
