# Panduan Maintainer Pusat Panduan

## Menambah artikel baru

1. Pilih direktori peran: `anggota/`, `admin-koperasi/`,
   `manajer-koperasi/`, `pengurus-koperasi/`, atau `shared/`
   untuk semua peran.
2. Buat file `nama-artikel.md` dengan frontmatter lengkap (lihat
   `README.md` di direktori ini).
3. Pastikan `slug` unik dan `route_names` merujuk ke route yang
   dideklarasikan di `routes/web.php` atau `routes/settings.php`.
4. Tambahkan entri di `role-workflow-inventory.md`.
5. Jalankan `npm run docs:validate` dan `npm run docs:screenshots`.

## Memperbarui artikel

1. Perbarui konten Markdown.
2. Perbarui `last_reviewed_commit` jika aplikasi (route,
   controller, permission, atau UI) berubah. Lihat penjelasan
   di bawah.
3. Jalankan validator sebelum commit.

## Makna `last_reviewed_commit`

Field `last_reviewed_commit` menunjuk **commit aplikasi** yang
menjadi basis validasi workflow artikel — bukan commit
dokumentasi. Saat validator dijalankan, field ini diverifikasi:

1. `git cat-file -e <sha>^{commit}` — commit harus ada.
2. `git merge-base --is-ancestor <sha> HEAD` — commit harus
   menjadi ancestor dari HEAD.

**Kapan memperbarui:**

- Jika route, controller, permission, atau UI yang dirujuk
  artikel berubah, perbarui field ke commit aplikasi terbaru
  yang berisi perubahan tersebut.
- Jika hanya teks artikel yang diperbaiki tanpa perubahan
  aplikasi, field tidak perlu dinaikkan.

**Strategi aman:**

- Gunakan SHA pendek dari `origin/main` (base aplikasi) jika
  artikel direview terhadap state aplikasi terbaru.
- Jangan mengisi dengan SHA HEAD cabang dokumentasi yang belum
  memiliki kode aplikasi baru.

## Menambah route

Tambahkan route di `routes/web.php` atau `routes/settings.php`.
Sistem akan memvalidasi setiap `route_names` di artikel dan
melaporkan route yang tidak ada.

## Menambah screenshot

1. Tambahkan baseline PNG di
   `tests/visual/baselines/<viewport>/<entry>.png`.
2. Daftarkan entri di
   `resources/docs/user-guide/screenshots.json`.
3. Referensikan di `screenshot_entries` artikel.
4. Jalankan `npm run docs:screenshots` untuk menyalin ke
   `public/docs/user-guide/screens/`.

## Menghapus artikel

1. Hapus file Markdown.
2. Hapus referensi `related_articles` di artikel lain.
3. Perbarui `role-workflow-inventory.md`.

## Catatan keamanan

- Jangan sertakan `.env`, kunci, atau kredensial produksi di body
  artikel.
- Validator (`npm run docs:validate`) menolak email akun Playwright
  dan referensi produksi langsung ke baseline.
- Frontmatter diverifikasi terhadap
  `App\Documentation\ArticleFrontmatter::REQUIRED_KEYS`.
