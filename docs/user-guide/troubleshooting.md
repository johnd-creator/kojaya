# Troubleshooting Pusat Panduan

## Artikel tidak muncul

1. Pastikan frontmatter memiliki `status: published`.
2. Periksa `roles` — `all` menampilkan ke semua peran; nama peran
   harus cocok dengan `ArticleRepository::resolveTargetRoles()`.
3. Jika `permissions` tidak kosong, pastikan user memiliki izin
   yang diminta. Perhatikan `permission_mode` (`all` atau `any`).
4. Jalankan `php artisan docs:audit-drift` (nama sebenarnya:
   `docs:audit-drift`) untuk memastikan tidak ada `route('…')`
   yang mengambang.

## Markdown tampil mentah

- Frontmatter tidak valid: editor tidak akan memuat artikel dan
  `ArticleRepository::loadAll()` melempar
  `InvalidArticleException`. Lihat log Laravel.
- Body tanpa frontmatter: artikel diabaikan (lihat validator).

## Screenshot tidak muncul

- `screenshot_entries` harus cocok dengan entri di
  `resources/docs/user-guide/screenshots.json`.
- File screenshot harus ada di `public/docs/user-guide/screens/`
  setelah `npm run docs:screenshots`.
- File `tests/visual/baselines/<entry>.png` harus ada (jangan
  diubah kecuali review manusia).
