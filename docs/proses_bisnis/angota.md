# Proses Bisnis Role Anggota Koperasi

## Ringkasan

Role anggota koperasi menggunakan Kojayaku sebagai kanal self-service untuk melihat status keanggotaan, simpanan, tagihan, pembayaran, pinjaman, poin, reward, transaksi POS, SHU, notifikasi, dan tiket bantuan.

Kojayaku terhubung dengan KojayaPro melalui database dan API yang sama. Anggota hanya boleh mengakses data miliknya sendiri, sedangkan verifikasi, approval, pencatatan akhir, dan koreksi data dilakukan oleh operator atau pengurus koperasi di KojayaPro.

## Aktor Yang Terlibat

| Aktor | Peran |
|---|---|
| Calon anggota | Mendaftar sebagai anggota koperasi dan menunggu verifikasi |
| Anggota koperasi | Mengakses layanan self-service Kojayaku |
| Operator koperasi | Memverifikasi anggota, pembayaran, pinjaman, reward, dan tiket bantuan |
| Pengurus koperasi | Menyetujui keputusan penting seperti pinjaman, SHU, dan kebijakan koperasi |
| Sistem Kojayaku | Menampilkan data dan menerima input anggota |
| Sistem KojayaPro | Mengelola data master, approval, ledger, POS, poin, pinjaman, dan laporan |

## Hak Akses Anggota

Anggota dapat:

- Login ke portal atau mobile app Kojayaku.
- Melihat dashboard ringkas keanggotaan.
- Melihat profil dan memperbarui data dasar.
- Melihat saldo simpanan, ledger, tagihan iuran, dan riwayat pembayaran.
- Mengunggah bukti pembayaran tagihan.
- Mengajukan pinjaman dan memantau status pinjaman.
- Melihat saldo poin, histori poin, katalog reward, dan mengajukan penukaran reward.
- Melihat transaksi POS yang terhubung dengan nomor anggota.
- Melihat alokasi SHU yang sudah ditutup oleh koperasi.
- Melihat notifikasi.
- Membuat tiket bantuan atau komplain.

Anggota tidak dapat:

- Mengubah saldo simpanan secara langsung.
- Menyetujui pembayaran sendiri.
- Menyetujui atau mencairkan pinjaman sendiri.
- Mengubah poin secara manual.
- Mengubah hasil pembagian SHU.
- Mengakses data anggota lain.

## Proses 1: Registrasi dan Aktivasi Anggota

### Tujuan

Mendaftarkan calon anggota agar memiliki akun dan data anggota yang valid di sistem koperasi.

### Alur Utama

1. Calon anggota mengisi formulir registrasi akun.
2. Sistem membuat akun pengguna awal.
3. Operator koperasi memeriksa data calon anggota.
4. Operator menghubungkan akun pengguna dengan data `CooperativeMember`.
5. Operator menetapkan organisasi, nomor anggota, status anggota, dan data kontak.
6. Jika data valid, anggota diaktifkan.
7. Anggota dapat login ke Kojayaku.

### Output

- Akun user aktif.
- Data anggota koperasi aktif.
- Akun terhubung ke `cooperative_member_id`.

### Kondisi Gagal

- Data identitas tidak lengkap.
- Email sudah digunakan.
- Akun belum terhubung ke data anggota.
- Status anggota belum aktif.

## Proses 2: Login dan Akses Dashboard

### Tujuan

Memberikan akses aman ke data pribadi anggota.

### Alur Utama

1. Anggota memasukkan email dan password.
2. Sistem memvalidasi kredensial.
3. Untuk mobile API, sistem menerbitkan token Sanctum dengan ability anggota.
4. Sistem memeriksa apakah user memiliki relasi anggota koperasi.
5. Sistem menampilkan dashboard Kojayaku.
6. Dashboard memuat ringkasan saldo simpanan, tagihan tertunda, pinjaman aktif, sisa pinjaman, poin, tier anggota, dan notifikasi belum dibaca.

### Output

- Sesi web atau token mobile aktif.
- Dashboard anggota tampil.

### Kontrol Bisnis

- Data yang tampil harus berdasarkan anggota yang sedang login.
- Jika akun belum terhubung ke anggota koperasi, sistem menolak akses.

## Proses 3: Pengelolaan Profil Anggota

### Tujuan

Memungkinkan anggota memperbarui data kontak dasar.

### Alur Utama

1. Anggota membuka menu Profil.
2. Sistem menampilkan data user dan data anggota.
3. Anggota memperbarui nama, email, nomor telepon, atau alamat.
4. Sistem memvalidasi format data.
5. Sistem menyimpan perubahan ke user dan data anggota.
6. Sistem menampilkan konfirmasi perubahan berhasil.

### Output

- Data profil anggota diperbarui.

### Catatan Proses

Data sensitif seperti NIK, dokumen KYC, rekening bank, dan perubahan status anggota sebaiknya tetap melalui verifikasi operator sebelum dipakai untuk transaksi koperasi.

## Proses 4: Cek Simpanan, Ledger, dan Tagihan

### Tujuan

Memberikan transparansi kepada anggota terkait saldo simpanan dan kewajiban iuran.

### Alur Utama

1. Anggota membuka menu Simpanan.
2. Sistem menghitung saldo dari ledger anggota.
3. Sistem menampilkan ringkasan saldo, total pembayaran approved, jumlah tagihan tertunda, dan nominal tagihan belum lunas.
4. Anggota melihat mutasi ledger berdasarkan tanggal posting.
5. Anggota melihat daftar invoice iuran, jenis kontribusi, periode, nominal, status, dan sisa tagihan.
6. Anggota melihat riwayat pembayaran beserta status verifikasi.

### Output

- Saldo simpanan terlihat.
- Riwayat mutasi dan tagihan terlihat.
- Anggota mengetahui kewajiban yang perlu dibayar.

### Status Tagihan

| Status | Makna Bisnis |
|---|---|
| UNPAID | Tagihan belum dibayar |
| PARTIAL | Tagihan dibayar sebagian |
| PAID | Tagihan lunas |

### Status Pembayaran

| Status | Makna Bisnis |
|---|---|
| PENDING | Bukti pembayaran dikirim dan menunggu verifikasi |
| APPROVED | Pembayaran disetujui dan memengaruhi invoice atau ledger |
| REJECTED | Pembayaran ditolak dan perlu diperbaiki |

## Proses 5: Upload Bukti Pembayaran

### Tujuan

Memungkinkan anggota melaporkan pembayaran iuran tanpa datang ke kantor koperasi.

### Alur Utama

1. Anggota memilih invoice yang masih UNPAID atau PARTIAL.
2. Anggota mengisi nominal pembayaran, metode pembayaran, tanggal bayar, nomor referensi opsional, catatan opsional, dan file bukti.
3. Sistem memvalidasi invoice milik anggota yang sedang login.
4. Sistem menyimpan file bukti pembayaran.
5. Sistem membuat data pembayaran dengan status PENDING.
6. Operator koperasi memeriksa bukti pembayaran di KojayaPro.
7. Jika valid, operator approve pembayaran.
8. Sistem memperbarui status pembayaran, invoice, dan ledger sesuai aturan layanan pembayaran.
9. Jika tidak valid, operator reject pembayaran dan anggota menerima status penolakan.

### Output

- Bukti pembayaran tercatat.
- Pembayaran menunggu verifikasi atau selesai diverifikasi.

### Kontrol Bisnis

- Anggota hanya boleh mengunggah bukti untuk invoice miliknya.
- Pembayaran tidak boleh langsung dianggap lunas sebelum operator atau gateway memverifikasi.
- File bukti hanya boleh memakai format yang diizinkan oleh request validasi.

## Proses 6: Pengajuan Pinjaman

### Tujuan

Memungkinkan anggota mengajukan pinjaman dan memantau proses persetujuannya.

### Alur Utama

1. Anggota membuka menu Pinjaman.
2. Sistem menampilkan daftar pinjaman anggota dan jenis pinjaman aktif.
3. Anggota memilih jenis pinjaman.
4. Anggota mengisi nominal pokok, tenor, tanggal jatuh tempo pertama, dan data lain yang diwajibkan.
5. Sistem memvalidasi input pengajuan.
6. Sistem membuat pengajuan pinjaman atas nama anggota yang sedang login.
7. Sistem menghitung jadwal angsuran melalui layanan pinjaman.
8. Pengajuan masuk ke proses review operator atau pengurus.
9. Pengurus menyetujui atau menolak pengajuan.
10. Jika disetujui, pinjaman dapat dicairkan oleh pihak berwenang.
11. Setelah aktif, anggota dapat melihat status pinjaman, angsuran, pembayaran, dan outstanding.

### Output

- Pengajuan pinjaman tercatat.
- Jadwal angsuran terbentuk.
- Status pinjaman dapat dipantau anggota.

### Status Pinjaman

| Status | Makna Bisnis |
|---|---|
| PENDING | Pengajuan baru menunggu review |
| APPROVED | Pengajuan disetujui, menunggu proses lanjutan |
| REJECTED | Pengajuan ditolak |
| ACTIVE | Pinjaman sudah aktif dan memiliki kewajiban angsuran |
| PAID_OFF | Pinjaman sudah lunas |

### Kontrol Bisnis

- Anggota tidak boleh memilih `cooperative_member_id` sendiri dari frontend.
- Sistem wajib mengambil anggota dari akun login.
- Detail pinjaman hanya boleh dibuka oleh pemilik pinjaman.
- Approval dan pencairan tetap berada di role operator atau pengurus.

## Proses 7: Pembayaran Angsuran Pinjaman

### Tujuan

Mencatat pembayaran angsuran pinjaman dan mengurangi outstanding anggota.

### Alur Utama Saat Ini

1. Anggota melihat daftar pinjaman dan outstanding.
2. Anggota membayar angsuran melalui kanal koperasi yang berlaku.
3. Operator mencatat pembayaran angsuran di KojayaPro.
4. Sistem memperbarui jadwal angsuran, pembayaran pinjaman, dan outstanding.
5. Anggota melihat status terbaru di Kojayaku.

### Pengembangan Yang Disarankan

- Anggota dapat mengunggah bukti bayar angsuran dari Kojayaku.
- Sistem menampilkan status angsuran: belum jatuh tempo, jatuh tempo, terlambat, dibayar sebagian, dan lunas.
- Sistem menampilkan penalti jika ada.

## Proses 8: Poin Anggota

### Tujuan

Memberikan transparansi atas poin yang diperoleh dari transaksi dan aktivitas koperasi.

### Alur Utama

1. Anggota melakukan transaksi POS dengan identitas anggota.
2. Sistem POS mencatat transaksi dan menghubungkan transaksi ke anggota.
3. Sistem menghitung poin berdasarkan aturan poin koperasi.
4. Sistem membuat transaksi poin.
5. Anggota membuka menu Poin.
6. Sistem menampilkan saldo poin, poin masuk, poin keluar, tier anggota, target tier berikutnya, dan histori poin.

### Output

- Poin anggota bertambah atau berkurang sesuai transaksi.
- Anggota dapat melihat histori poin dan saldo berjalan.

### Kontrol Bisnis

- Poin tidak diedit langsung oleh anggota.
- Poin keluar terjadi karena penukaran reward atau koreksi yang sah.
- Riwayat poin harus dapat ditelusuri ke transaksi sumber.

## Proses 9: Penukaran Reward

### Tujuan

Memungkinkan anggota menukar poin dengan reward yang tersedia.

### Alur Utama

1. Anggota membuka menu Reward.
2. Sistem menampilkan katalog reward aktif, kebutuhan poin, stok, dan masa berlaku.
3. Anggota memilih reward, jumlah, dan alamat pengiriman.
4. Sistem memvalidasi saldo poin anggota.
5. Sistem memvalidasi status reward, stok, dan masa berlaku.
6. Sistem membuat redemption.
7. Sistem mengurangi poin anggota.
8. Operator memproses penyerahan atau pengiriman reward.
9. Anggota melihat status penukaran reward.

### Output

- Redemption reward tercatat.
- Saldo poin berkurang.
- Status penukaran dapat dipantau.

### Kontrol Bisnis

- Penukaran gagal jika poin tidak cukup.
- Reward tidak aktif atau stok habis tidak boleh ditukar.
- Pemenuhan reward tetap perlu diproses operator.

## Proses 10: Riwayat Transaksi POS

### Tujuan

Memberikan anggota akses ke riwayat belanja yang tercatat atas nomor anggotanya.

### Alur Utama

1. Anggota melakukan pembelian di POS koperasi.
2. Kasir memilih atau memasukkan anggota pada transaksi POS.
3. Sistem menyimpan transaksi, item, pembayaran, dan relasi anggota.
4. Anggota membuka menu Transaksi.
5. Sistem menampilkan transaksi POS anggota, item produk, tanggal transaksi, dan pembayaran.

### Output

- Anggota dapat memeriksa riwayat belanja.
- Transaksi dapat menjadi dasar poin atau SHU sesuai kebijakan koperasi.

### Kontrol Bisnis

- Transaksi hanya muncul jika transaksi POS terhubung ke anggota.
- Koreksi transaksi POS dilakukan oleh kasir atau operator, bukan anggota.

## Proses 11: SHU Anggota

### Tujuan

Menampilkan hak SHU anggota setelah periode SHU ditutup oleh koperasi.

### Alur Utama

1. Pengurus atau operator menghitung SHU periode berjalan di KojayaPro.
2. Sistem menghitung alokasi SHU berdasarkan aturan koperasi, seperti partisipasi transaksi, simpanan, atau parameter lain.
3. Pengurus menutup periode SHU.
4. Sistem menyimpan alokasi SHU per anggota.
5. Anggota membuka menu atau endpoint SHU.
6. Sistem hanya menampilkan periode CLOSED yang memiliki alokasi untuk anggota tersebut.

### Output

- Anggota dapat melihat riwayat dan nilai SHU yang sudah final.

### Kontrol Bisnis

- SHU yang belum ditutup tidak ditampilkan sebagai hak final.
- Anggota hanya melihat alokasi SHU miliknya.

## Proses 12: Notifikasi Anggota

### Tujuan

Memberikan informasi penting kepada anggota secara cepat.

### Alur Utama

1. Sistem atau operator membuat notifikasi.
2. Notifikasi dikirim ke user anggota.
3. Anggota membuka menu Notifikasi.
4. Sistem menampilkan daftar notifikasi terbaru.
5. Dashboard menghitung notifikasi belum dibaca.

### Contoh Notifikasi

- Pembayaran diterima atau ditolak.
- Pinjaman disetujui atau ditolak.
- Jatuh tempo tagihan atau angsuran.
- Reward siap diambil.
- SHU periode tertentu sudah tersedia.

## Proses 13: Tiket Bantuan dan Komplain

### Tujuan

Memberikan kanal resmi bagi anggota untuk bertanya atau mengajukan koreksi.

### Alur Utama

1. Anggota membuka menu bantuan.
2. Anggota mengisi kategori, prioritas, subjek, dan pesan.
3. Sistem membuat tiket dengan status OPEN.
4. Operator koperasi memeriksa tiket.
5. Operator melakukan tindak lanjut, koreksi, atau eskalasi ke pengurus.
6. Tiket ditutup setelah masalah selesai.

### Kategori Tiket

| Kategori | Contoh Kasus |
|---|---|
| GENERAL | Pertanyaan umum layanan koperasi |
| PAYMENT | Bukti bayar belum diverifikasi |
| LOAN | Pertanyaan pengajuan atau angsuran pinjaman |
| SAVINGS | Koreksi saldo atau ledger simpanan |
| POINTS | Poin transaksi belum masuk |
| PROFILE | Data profil perlu diperbaiki |
| POS | Komplain transaksi toko koperasi |

## Proses 14: Logout

### Tujuan

Mengakhiri sesi anggota dengan aman.

### Alur Utama

1. Anggota memilih logout.
2. Untuk web, sistem mengakhiri sesi.
3. Untuk mobile, sistem mencabut token saat ini.
4. Jika logout semua perangkat dipilih, sistem mencabut seluruh token user.
5. Anggota harus login ulang untuk mengakses Kojayaku.

## Ringkasan Menu Anggota

| Menu | Fungsi Utama | Data Sumber |
|---|---|---|
| Dashboard | Ringkasan simpanan, pinjaman, poin, transaksi, notifikasi | Member, ledger, invoice, loan, point, notification |
| Simpanan | Saldo, ledger, invoice, pembayaran | Cooperative ledger, dues invoice, payment |
| Pinjaman | Pengajuan dan tracking pinjaman | Loan, loan type, installment, payment |
| Poin | Saldo dan histori poin | Point transaction, redemption |
| Reward | Katalog dan penukaran reward | Reward, reward redemption |
| Transaksi | Riwayat transaksi POS | POS transaction, POS item, POS payment |
| Profil | Data anggota dan user | User, cooperative member |
| SHU | Riwayat alokasi SHU | SHU period, SHU allocation |
| Notifikasi | Informasi sistem untuk anggota | User notification |
| Bantuan | Tiket support atau komplain | Cooperative support ticket |

## Integrasi Dengan KojayaPro

| Proses Anggota | Proses Admin/Operator |
|---|---|
| Registrasi anggota | Verifikasi dan aktivasi anggota |
| Update profil | Review data sensitif jika diperlukan |
| Upload bukti pembayaran | Verifikasi pembayaran dan update invoice |
| Pengajuan pinjaman | Review, approve, reject, dan pencairan |
| Pembayaran angsuran | Pencatatan pembayaran pinjaman |
| Transaksi POS | Penjualan oleh kasir dan perhitungan poin |
| Penukaran reward | Pemenuhan reward oleh operator |
| SHU | Perhitungan dan closing periode SHU |
| Tiket bantuan | Tindak lanjut dan penyelesaian komplain |

## Endpoint Self-Service Anggota

Endpoint mobile self-service anggota berada di namespace `/api/v1/member` dan memakai autentikasi Sanctum.

| Endpoint | Fungsi |
|---|---|
| `GET /api/v1/member/dashboard` | Ringkasan dashboard anggota |
| `GET /api/v1/member/profile` | Detail profil anggota |
| `PUT /api/v1/member/profile` | Update profil dasar |
| `GET /api/v1/member/savings/summary` | Ringkasan simpanan dan tagihan |
| `GET /api/v1/member/savings/ledger` | Mutasi ledger dengan saldo berjalan |
| `GET /api/v1/member/dues/invoices` | Daftar tagihan iuran |
| `GET /api/v1/member/payments` | Riwayat pembayaran |
| `POST /api/v1/member/payments/proof` | Upload bukti pembayaran |
| `GET /api/v1/member/loans` | Daftar pinjaman |
| `POST /api/v1/member/loans` | Pengajuan pinjaman |
| `GET /api/v1/member/loans/{loan}` | Detail pinjaman milik anggota |
| `GET /api/v1/member/shu` | Riwayat SHU anggota |
| `GET /api/v1/member/notifications` | Daftar notifikasi |
| `GET /api/v1/member/support-tickets` | Daftar tiket bantuan |
| `POST /api/v1/member/support-tickets` | Buat tiket bantuan |

## Aturan Data dan Keamanan

- Semua data anggota harus difilter berdasarkan user login.
- API anggota harus memakai ability `member:read` untuk baca dan `member:write` untuk aksi tulis.
- ID anggota tidak boleh diambil dari input bebas untuk aksi self-service.
- Bukti pembayaran dan dokumen perlu validasi ukuran, format, dan lokasi penyimpanan.
- Perubahan finansial harus menghasilkan jejak audit melalui proses operator atau service terkait.
- Status final seperti pembayaran approved, pinjaman aktif, dan SHU closed hanya boleh ditetapkan oleh role berwenang.

## Risiko dan Gap Yang Perlu Ditutup

| Area | Gap | Rekomendasi |
|---|---|---|
| Pembayaran | Payment gateway belum end-to-end | Tambahkan QRIS, VA, webhook, receipt, dan rekonsiliasi otomatis |
| Pinjaman | Upload dokumen dan simulasi resmi belum lengkap | Tambahkan dokumen pendukung, kalkulator API, dan alasan penolakan |
| Simpanan | Statement PDF belum tersedia | Tambahkan export statement per periode |
| Profil | KYC dan rekening bank belum lengkap | Tambahkan data KYC dengan approval operator |
| Angsuran | Upload bukti angsuran dari anggota belum tersedia | Samakan flow dengan bukti pembayaran iuran |
| Support | Workflow penyelesaian tiket belum detail | Tambahkan status, assignment, SLA, dan riwayat balasan |
| Notifikasi | Push notification produksi masih roadmap | Integrasikan FCM atau kanal notifikasi lain |

## Indikator Keberhasilan

- Anggota dapat login dan melihat dashboard tanpa bantuan operator.
- Anggota dapat memahami saldo simpanan dan tagihan tertunda.
- Bukti pembayaran dapat dikirim dan diverifikasi.
- Pengajuan pinjaman dapat dibuat dan statusnya dapat dipantau.
- Riwayat transaksi POS, poin, reward, dan SHU transparan.
- Komplain anggota tercatat sebagai tiket, bukan percakapan manual yang hilang.
- Operator dapat menindaklanjuti semua aksi anggota dari KojayaPro.
