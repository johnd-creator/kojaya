# Kojaya — Pusat Panduan In-App

Direktori ini adalah **sumber otoritatif tunggal** untuk pusat panduan
in-app di `/documentation`. Setiap artikel adalah file Markdown
dengan YAML frontmatter yang menjelaskan peran, izin, rute, dan
referensi yang relevan.

## Cara kerja

1. Backend membaca direktori ini melalui
   `App\Documentation\ArticleRepository`. Tidak ada tabel database
   untuk artikel.
2. Setiap artikel mempunyai frontmatter dengan kunci wajib
   (lihat `App\Documentation\ArticleFrontmatter::REQUIRED_KEYS`).
3. Otorisasi per artikel mengikuti
   `App\Documentation\ArticleAuthorizer`:
   - `roles` cocok dengan `PrimaryRoleResolver` atau pemetaan
     multi-role (`ArticleRepository::resolveTargetRoles`).
   - `permissions` dievaluasi sesuai `permission_mode`
     (`all` atau `any`).
4. Render Markdown mengikuti
   `App\Documentation\ArticleRenderer` — sanitasi tag dan atribut
   HTML, tautan relatif aman, dan daftar isi otomatis.

## Struktur direktori

```
docs/user-guide/
├── README.md                       (file ini)
├── content-correction-audit.md     (laporan audit)
├── anggota/                        (artikel untuk peran Anggota)
├── admin-koperasi/                 (artikel untuk Admin Koperasi)
├── manajer-koperasi/               (artikel untuk Manajer Koperasi)
├── pengurus-koperasi/              (artikel untuk Pengurus Koperasi)
├── shared/                         (artikel untuk semua peran)
├── role-workflow-inventory.md      (inventaris workflow)
├── role-responsibility-matrix.md   (matriks peran × izin)
├── terminology.md                  (alias glosarium)
├── troubleshooting.md
├── architecture.md
└── maintainer-guide.md
```

## Frontmatter wajib

```yaml
title: string                       # judul artikel
slug: kebab-case-string             # identifikasi artikel
summary: string                     # deskripsi singkat
category: string                    # kategori dan heading
module: string                      # modul aplikasi yang terkait
roles:                              # peran target
  - all | anggota | admin_koperasi | manajer_koperasi | pengurus_koperasi
permissions: []                     # daftar permission (boleh kosong)
permission_mode: all|any            # mode pencocokan permission
route_names: []                     # daftar nama route terkait
risk_level: low|medium|high         # tingkat risiko
screenshot_entries: []              # ID screenshot pipeline
related_articles: []                # slug artikel terkait
last_reviewed_commit: 7-40-char-sha # commit terakhir review
status: published|draft|archived    # status publikasi
sort_order: int                     # urutan tampil
```

## Memvalidasi

Validator Node `scripts/validate-user-guide.mjs` (npm script
`docs:validate`) memeriksa:

- kelengkapan frontmatter
- keunikan `slug`
- validitas `roles`, `permissions`, `permission_mode`
- `route_names` terdaftar di `Route::getRoutes()`
- `related_articles` menunjuk ke `slug` yang ada
- `screenshot_entries` cocok dengan `resources/docs/user-guide/screenshots.json`
- tidak ada Markdown kosong, tidak ada kredensial, tidak ada
  referensi `.env`

Lihat laporan di `docs/user-guide/role-workflow-inventory.md` dan
`docs/user-guide/role-responsibility-matrix.md` untuk audit.
