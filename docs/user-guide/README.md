# Kojaya User Guide

Dokumen ini adalah pusat panduan penggunaan dan prosedur kerja
Kojaya untuk empat role utama:

1. [Anggota](anggota.md)
2. [Admin Koperasi](admin-koperasi.md)
3. [Manajer Koperasi](manajer-koperasi.md)
4. [Pengurus Koperasi](pengurus-koperasi.md)

## Sumber otoritatif

Artikel di direktori ini **identik** dengan artikel yang disajikan
oleh pusat panduan in-app di `/documentation` (lihat
[`App\Http\Controllers\Documentation\DocumentationController`](../app/Http/Controllers/Documentation/DocumentationController.php)).
Sumber otoritatif runtime adalah tabel `documentation_articles` yang
diisi oleh
[`Database\Seeders\DocumentationArticleSeeder`](../database/seeders/DocumentationArticleSeeder.php).

## Mengapa keduanya ada?

- **Markdown di repository** (`docs/user-guide/*.md`) → untuk
  peninjauan dalam PR, diff-able, dan pencarian lewat editor.
- **Database-backed** (`documentation_articles`) → untuk disajikan
  in-app dengan filter peran/permission dan update tanpa deploy
  (curated by System Admin via `manage_roles`).

## Validasi drift

Setiap perubahan route, permission, atau modul koperasi bisa membuat
referensi `route('…')` di artikel ini menjadi basi. Untuk
mendeteksi drift, jalankan:

```bash
php artisan docs:audit-drift --source=markdown
php artisan docs:audit-drift --source=database
```

Implementasi perintah ada di
[`App\Console\Commands\VerifyDocumentationRoutesCommand`](../app/Console/Commands/VerifyDocumentationRoutesCommand.php).
Perintah ini memindai pola `route('…')` di body artikel lalu
memverifikasi setiap nama route ada dalam daftar `Route::getRoutes()`.
Exit code `0` artinya lulus, non-zero artinya ada route yang
mengambang.

Perintah ini dipakai di CI (lihat test
[`ViewDocumentationCenterTest`](../tests/Feature/Documentation/ViewDocumentationCenterTest.php)
yang menjalankan `Artisan::call('docs:audit-drift')`).
