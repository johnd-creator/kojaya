# Plan 08 - Theming, Dark Mode, dan Aestetika Premium: Audit & Keputusan

> Audit hasil: 21–22 Juni 2026. Dokumen ini merekam hasil audit Plan 08 dan keputusan perbaikan yang diambil.
> Reviewer: Pengembang senior (peninjauan pertama & final).

## Ringkasan Eksekutif

Audit dilakukan terhadap shared components dan lima halaman prioritas aplikasi.
Mayoritas shared components (Button, Card, DataTable, Input, Textarea, Select,
EmptyState, PageContainer, StatusBadge) sudah menerapkan design tokens
konsisten untuk light dan dark mode. Ditemukan tiga kelompok inkonsistensi
yang diperbaiki pada iterasi ini.

## Cakupan Audit

### Shared Components (di-`resources/js/components/ui/**` dan `resources/js/components/**`)

| Komponen | Status | Catatan |
| --- | --- | --- |
| Button (`button/Button.vue`) | OK | CVA dengan 6 varian, focus-visible pakai token `ring-ring/50`, disabled `opacity-50` + `pointer-events-none` |
| Card (`card/Card.vue`) | DIPERBAIKI | Border/background diselaraskan dari `border-zinc-200/80 bg-white/95` (transparan) ke `border-zinc-200 bg-white` (solid) |
| DataTable (`data-table/DataTable.vue`) | OK | Sticky header sudah aktif, hover row `hover:bg-zinc-50 dark:hover:bg-zinc-800/50`, selected row `bg-sky-50/50 dark:bg-sky-950/20`, empty state, loading skeleton sudah ada |
| Input (`input/Input.vue`) | OK | `focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]`, `aria-invalid:ring-destructive/20` |
| Textarea (`textarea/Textarea.vue`) | OK | Pola sama dengan Input, design tokens dipakai konsisten |
| Select (`select/SelectTrigger.vue`, `SelectItem.vue`) | OK | `focus:ring-2 focus:ring-ring focus:ring-offset-2`, `data-[disabled]:opacity-50`, `focus:bg-accent` untuk item |
| SelectFilter (`components/SelectFilter.vue`) | OK | Native select dengan `border-zinc-200 dark:border-zinc-700`, `focus:ring-emerald-500` |
| EmptyState (`components/EmptyState.vue`) | OK | Icon `text-zinc-300 dark:text-zinc-700` (kontras rendah sesuai pola dekoratif), judul & deskripsi `text-zinc-900 dark:text-zinc-100` / `text-zinc-500 dark:text-zinc-400` |
| PageContainer (`components/PageContainer.vue`) | OK | Layout wrapper, tidak ada style yang bertentangan |
| StatusBadge (`status-badge/StatusBadge.vue`) | OK | Mapping status -> tone (emerald/amber/blue/destructive) konsisten di light + dark |
| FilterBar (`components/FilterBar.vue`) | OK | Pakai `bg-card` token (theme variable), bukan hardcode |
| PageHeader (`page-header/PageHeader.vue`) | OK | Gradient text & ring `ring-zinc-200/70 dark:ring-zinc-800/60` |
| Alert (`alert/Alert.vue`) | OK | Varian destructive + default dengan `bg-background text-foreground` |
| Badge (`badge/Badge.vue`) | OK | 5 varian dengan semantic tokens |
| Progress (`progress/Progress.vue`) | OK | Reka UI ProgressRoot, indicator neutral dengan `bg-primary/20` |
| JobProgressTracker (`components/JobProgressTracker.vue`) | DIPERBAIKI | Diselaraskan dengan pattern card aplikasi: `border-zinc-100 bg-white ... dark:bg-zinc-900/80` |
| FilterBar, StatsCard, SectionHeader, GradientKpiCard | OK | Komponen dekoratif dashboard, tone-based, konsisten di light + dark |

### Halaman Prioritas

| Halaman | Path | Status | Catatan |
| --- | --- | --- | --- |
| Dashboard | `pages/Dashboard.vue` | OK dengan catatan | Hero gradient & decorative orbs masih ada. Ini adalah pattern yang disengaja untuk hero/kartu utama, dan dikontrol dengan `aria-hidden` agar screen reader melewatinya. Tidak diubah untuk menghindari redesign. |
| Cooperative Payments | `pages/Cooperative/Payments/Index.vue` | OK | Hero gradient mengikuti pattern Dashboard; card menggunakan `bg-card` atau `bg-white/80` konsisten. |
| POS Reports | `pages/Cooperative/Pos/Reports/Index.vue` | DIPERBAIKI | Tabel `top_products` ditambah `py-2.5` (sebelumnya `py-2`), `tabular-nums` untuk angka, dan `hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40` untuk konsistensi density. Tracker JobProgressTracker terintegrasi tanpa crash pending state. |
| Payroll Index | `pages/Payroll/Index.vue` | OK | DataTable sudah dipakai; skeleton + empty state dihandle oleh DataTable. |
| Reports (laporan umum) | `pages/Cooperative/Reports/Index.vue` | OK | DataTable + FilterBar dipakai; tone & focus visible dari component sudah diterapkan. |

## Keputusan & Perubahan

### 1. `Card` - normalisasi background

**Sebelum:**
```
border-zinc-200/80 bg-white/95 ... dark:border-zinc-800/80 dark:bg-zinc-900/80
```

**Sesudah:**
```
border-zinc-200 bg-white ... dark:border-zinc-800 dark:bg-zinc-900
```

**Alasan:** Card surface di semua halaman pakai solid color (`border-zinc-200 bg-white dark:bg-zinc-900`). Token `/80` dan `/95` membuat background Card sedikit transparan dan terlihat tidak konsisten dengan container halaman. Solid color lebih cocok untuk ERP-style data density.

### 2. `JobProgressTracker` - selaraskan dengan pattern aplikasi

**Sebelum:**
```
border-zinc-200 bg-white ... dark:bg-zinc-900/70
```

**Sesudah:**
```
border-zinc-100 bg-white ... dark:bg-zinc-900/80
```

**Alasan:** Pattern `border-zinc-100` (bukan `border-zinc-200`) dan `dark:bg-zinc-900/80` adalah pattern card yang dipakai halaman POS Reports dan halaman lain. Menyamakan dua pattern ini membuat tracker tidak terlihat "asing".

### 3. Tabel `top_products` - density + hover state

**Sebelum:** `py-2`, tanpa hover, tanpa `tabular-nums`.

**Sesudah:** `py-2.5`, `hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40`, `tabular-nums` pada kolom numerik.

**Alasan:** Data density ERP menuntut angka-angka di tabel tetap sejajar (`tabular-nums`), padding minimal agar muat banyak baris, dan hover state untuk eksplorasi data.

## Yang Tidak Diubah (disengaja)

- **Hero gradient & decorative orbs di Dashboard / Payments / Payroll**: decorative element dengan `aria-hidden="true"` agar screen reader tidak membaca. Menghilangkannya akan mengurangi identitas visual koperasi. Smoke manual mengonfirmasi teks tetap terbaca dan orbs tidak overlap dengan konten.
- **Tone palette (emerald/amber/rose/sky/violet/zinc)**: sudah sesuai konvensi Plan 08. Tidak ada dominasi satu hue yang berlebihan di shared components.
- **`focus-visible` pada native `<select>` di Reports/Index.vue**: form filter laporan. Refactor ke Reka UI Select di luar scope Plan 08 (P3 follow-up). Native `<select>` tetap dipakai karena tidak ada keluhan aksesibilitas spesifik dan plan membatasi scope perbaikan visual saja.
- **Input dengan `focus-visible:ring-emerald-500` (di `Input.vue` sebelum refactor token)**: brand cooperative. Tetap dipakai oleh DataTable, FilterBar, dll. Mengganti ke token `ring-ring` akan menghilangkan identitas brand.

## Manual Smoke Notes — Hasil Verifikasi

Pemeriksaan visual sudah dilakukan pada tanggal 22 Juni 2026:

### Light mode
| Halaman | Status | Temuan |
| --- | --- | --- |
| `Dashboard` | ✅ OK | Hero gradient tidak dominan. Orbs `aria-hidden` tidak mengganggu text. Skeleton analytics muncul saat deferred. |
| `Cooperative/Payments/Index` | ✅ OK | Card gradient konsisten. Form submit menunjukkan loading state di tombol. Error state validation muncul. |
| `Cooperative/Pos/Reports/Index` | ✅ OK | Tabel `top_products` hover state berfungsi. Tracker JobProgressTracker visible saat pending/processing. Skeleton analytics muncul. |
| `Payroll/Index` | ✅ OK | DataTable sticky header tidak terpotong. Empty state "Belum ada data penggajian" muncul. |
| `Cooperative/Reports/Index` | ✅ OK | DataTable + FilterBar konsisten. Skeleton analytics untuk deferred summary. |

### Dark mode (`<html class="dark">` via DevTools)
| Halaman | Status | Temuan |
| --- | --- | --- |
| `Dashboard` | ✅ OK | Dark bg `zinc-900` konsisten. Hero gradient tidak terlalu kontras. Orbs tetap `aria-hidden`. |
| `Cooperative/Payments/Index` | ✅ OK | Card gradient `dark:border-emerald-900/40 dark:bg-zinc-900` konsisten. |
| `Cooperative/Pos/Reports/Index` | ✅ OK | Tabel dark hover state berfungsi. Tracker dark card `dark:bg-zinc-900/80`. |
| `Payroll/Index` | ✅ OK | DataTable skeleton dark mode OK. |
| `Cooperative/Reports/Index` | ✅ OK | Empty state dark mode OK. |

### Mobile (viewport 375px via DevTools)
| Item | Status | Temuan |
| --- | --- | --- |
| Horizontal scroll | ✅ OK | Hanya tabel yang punya `overflow-x-auto`. Card tidak overlap. |
| Header layout | ✅ OK | Breadcrumbs + title stack vertikal, tidak terpotong. |
| Filter form | ✅ OK | Grid filter berubah ke single column. Button "Terapkan" full width. |

### Fokus dan Disabled State
| Item | Status | Temuan |
| --- | --- | --- |
| `Tab` dari address bar | ✅ OK | Button, link, input, select, checkbox memiliki `focus-visible` ring. |
| Disabled button | ✅ OK | Submit form tanpa nilai → tombol `disabled` dengan `opacity-50`. |
| Selected row (DataTable) | ✅ OK | Checkbox select menunjukkan `bg-sky-50/50` (light) / `bg-sky-950/20` (dark). |

### Skeleton Loading
| Item | Status | Temuan |
| --- | --- | --- |
| Deferred analytics (Dashboard) | ✅ OK | Skeleton muncul saat `Deferred data="analytics"` fallback. |
| Deferred analytics (POS Reports) | ✅ OK | Skeleton cards + chart placeholder sebelum data termuat. |
| DataTable loading | ✅ OK | 5 baris skeleton setiap kolom, status `sr-only` untuk screen reader. |

## Verifikasi Otomatis

- `npm run build` (perubahan CSS tidak menambah error)
- `vendor/bin/pint --dirty --format agent` (tidak ada file PHP berubah)
- `php artisan test --compact tests/Feature/Cooperative/Plan07BackgroundJobExportTest.php` (lulus 13 test)

## Risiko

- Perubahan pada `Card` mungkin mempengaruhi halaman yang memberi class override pada Card. Audit menunjukkan tidak ada halaman yang override background Card, hanya border dan shadow.
- `JobProgressTracker` hanya dipakai oleh halaman POS Reports. Perubahan hanya berdampak di sana.

## Verifikasi Manual Lanjutan — Hasil

Verifikasi manual sudah dilakukan pada 22 Juni 2026. Semua butir sudah terverifikasi:

| No | Langkah | Hasil | Keputusan |
| --- | --- | --- | --- |
| 1 | Dashboard light + dark: hero gradient & orbs | ✅ Tidak mengganggu readability. Orbs `aria-hidden`. Teks tetap kontras. | Terima. Tidak perlu perubahan. |
| 2 | Payments form: loading & error state | ✅ Tombol loading muncul saat submit. Validation error muncul inline. | Terima. |
| 3 | POS Reports: tabel hover + tracker | ✅ `top_products` hover state berfungsi di light & dark. Tracker visible saat pending/processing. | Terima. |
| 4 | Payroll + Reports: DataTable sticky header + selected row | ✅ Sticky header tidak terpotong. Selected row background OK di light (`bg-sky-50/50`) dan dark (`bg-sky-950/20`). | Terima. |
| 5 | Mobile 375px: scroll & overlap | ✅ Tidak ada horizontal scroll kecuali di DataTable (overflow-x-auto sudah handle). Card tidak overlap. | Terima. |

Kesimpulan: **Plan 08 siap ditutup. Tidak ada temuan blocking.**

## Referensi

- `docs/improve/08-theming-dark-mode.md` - Plan 08
- `docs/improve/checklist.md` - Checklist review
- [Tailwind v4 Custom Variants](https://tailwindcss.com/docs/adding-custom-styles)
