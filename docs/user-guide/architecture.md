# Arsitektur Pusat Panduan

Pusat panduan mengikuti pola **file-backed repository** dengan
otorisasi berbasis peran yang konsisten dengan aplikasi.

## Komponen

| Komponen | Lokasi | Tanggung jawab |
| --- | --- | --- |
| `Article` | `app/Documentation/Article.php` | Value object artikel + parser frontmatter. |
| `ArticleFrontmatter` | `app/Documentation/ArticleFrontmatter.php` | Validasi payload frontmatter. |
| `ArticleRepository` | `app/Documentation/ArticleRepository.php` | Pemindai direktori, filter role/permission, cache. |
| `ArticleAuthorizer` | `app/Documentation/ArticleAuthorizer.php` | Otorisasi per artikel (menggantikan `DocumentationPolicy`). |
| `ArticleRenderer` | `app/Documentation/ArticleRenderer.php` | Markdown → HTML aman + TOC otomatis. |
| `ContextualHelpRegistry` | `app/Documentation/ContextualHelpRegistry.php` | Pemetaan `route → slug → role → permission → screenshot_state`. |
| `DocumentationController` | `app/Http/Controllers/Documentation/DocumentationController.php` | Inertia responses. |
| `DocumentationServiceProvider` | `app/Providers/DocumentationServiceProvider.php` | Singleton binding repository, authorizer. |

## Alur data

```
docs/user-guide/**/*.md
       │
       ▼
ArticleRepository.loadAll()
       │  (parse YAML, validate, sort)
       ▼
ArticleAuthorizer.filterVisible(user)
       │  (roles + permissions + permission_mode)
       ▼
DocumentationController.index / show
       │  (Inertia props)
       ▼
resources/js/pages/Documentation/{Index,Show}.vue
```

## Caching

`ArticleRepository` menyimpan hasil `loadAll()` di memori dan dapat
memanfaatkan cache Laravel (`setCache()`). TTL default 1 jam; flush
otomatis pada mode `--no-interaction` validator.

## Sanitasi

Renderer menggunakan allow-list elemen dan atribut HTML. Skrip,
event handler, dan URI non-aman dihapus. Tag yang tidak dikenal
diubah menjadi teks.
