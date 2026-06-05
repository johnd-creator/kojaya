# Kritik dan Saran M3 - KojayaPro & Kojayaku

Dokumen ini adalah versi koreksi dari kritik awal terhadap repositori `kojaya`. Saya setuju dengan arah besarnya: aplikasi sudah punya fondasi arsitektur yang cukup sehat, tetapi ada beberapa titik yang perlu dirapikan sebelum produksi serius. Namun, sebagian kritik awal terlalu spesifik tanpa bukti yang cukup, sehingga saya koreksi agar lebih adil, defensible, dan bisa langsung dijadikan rencana kerja.

## Sikap Saya Terhadap Kritik Awal

Saya setuju dengan beberapa poin utama:

- Service-oriented monolith adalah pilihan yang masuk akal untuk ERP koperasi pada tahap ini.
- Risiko terbesar bukan pada framework atau stack, melainkan pada konsistensi eksekusi: status string, controller yang masih memegang business orchestration, observability, dan hygiene repo.
- Modul payroll, loan, notification, dan mobile API harus diprioritaskan karena berdampak langsung pada uang, kepatuhan, dan pengalaman anggota.
- Root repo memang berisi beberapa artefak pribadi/probe yang seharusnya tidak menjadi bagian dari source utama.

Saya tidak sepenuhnya setuju dengan beberapa klaim awal:

- Jangan menyebut "CI belum terlihat"; repo sudah memiliki `.github/workflows/ci.yml` dan `.github/workflows/deploy.yml`.
- Jangan menyimpulkan semua optional dependency frontend bermasalah hanya karena ada binary lintas OS. Mereka berada di `optionalDependencies`, jadi lebih tepat disebut "perlu dipantau", bukan bug pasti.
- Jangan menyebut OpenAPI tidak punya snapshot test; dokumen/log menunjukkan snapshot API sudah menjadi bagian hardening.
- Jangan menyarankan migrasi UUID untuk `Loan` sebagai quick win. Itu perubahan besar dengan risiko tinggi, dan harus diperlakukan sebagai major migration.
- Jangan menyatakan policy/API tertentu lemah tanpa audit method dan test negatif yang spesifik.

## Cakupan Tinjauan

Berdasarkan inspeksi lokal pada 2 Juni 2026:

- 114 model di `app/Models`
- 66 service di `app/Services`
- 100 controller di `app/Http/Controllers`
- 122 migration di `database/migrations`
- 109 test file
- 53 factory
- CI dan deploy workflow tersedia di `.github/workflows`

Dokumen ini tetap berupa tinjauan statis. Beberapa temuan perlu dibuktikan lagi dengan test, query, atau profiling sebelum dieksekusi sebagai perubahan besar.

## Kekuatan Utama

### 1. Arsitektur Umum Sudah Tepat

`docs/architecture.md` mendeklarasikan pola service-oriented monolith. Untuk ERP koperasi, ini pilihan pragmatis: cukup modular untuk domain payroll, koperasi, POS, project, dan compliance, tetapi belum memaksa kompleksitas microservices.

### 2. Stack Modern dan Konsisten

Laravel 12, Vue 3, Inertia v2, TypeScript, Tailwind v4, Sanctum, Fortify, Wayfinder, dan PostgreSQL adalah kombinasi yang koheren. Wayfinder juga membantu menjaga route frontend tetap type-safe.

### 3. Domain Compliance Indonesia Sudah Dipikirkan

BPJS, PPh21 TER, e-Faktur, Midtrans, WhatsApp notification, payroll, koperasi simpan pinjam, dan POS sudah muncul sebagai domain nyata, bukan hanya CRUD generik.

### 4. API Mobile Sudah Lebih Matang

ADR dan log menunjukkan API mobile sudah bergerak ke arah response allowlist, token abilities, API envelope, request id, dan OpenAPI snapshot. Ini bagus untuk Kojayaku.

### 5. Test Suite Tidak Kecil

109 test file dan 53 factory menunjukkan proyek tidak berada pada kondisi tanpa pengaman. Ini modal penting untuk refactor domain payroll, loan, dan notification.

## Risiko Utama Yang Saya Anggap Valid

### 1. `PayrollController::generate` Masih Terlalu Berat

Temuan ini valid. Method `generate()` masih melakukan banyak orchestration langsung:

- mengambil employee aktif;
- menghitung PPh21;
- menghitung BPJS;
- menghitung lembur;
- membuat payroll;
- insert komponen payroll;
- membuat overtime payment;
- menentukan status payroll.

Masalah utamanya bukan sekadar jumlah baris. Masalahnya adalah proses finansial lintas-domain dilakukan di controller dan tidak terlihat dibungkus `DB::transaction()`.

Saran koreksi:

- Buat `PayrollGenerationService`.
- Bungkus proses dengan `DB::transaction()`.
- Tetapkan kontrak hasil seperti `generated`, `skipped`, dan `failed`.
- Jaga idempotency: pemanggilan dua kali untuk periode yang sama tidak boleh membuat payroll ganda.
- Tambahkan feature test untuk happy path, idempotency, dan rollback ketika salah satu employee gagal diproses.

Prioritas: tinggi.

### 2. Magic String Status Masih Terlalu Banyak

Temuan ini valid. Contoh yang terlihat:

- `'status' => 'DRAFT'`
- `'status' => 'PENDING'`
- `where('status', 'ACTIVE')`

Ini muncul di controller, service, seeder, dan factory. Sebagian domain sudah punya enum, tetapi penggunaannya belum merata.

Saran koreksi:

- Buat ADR "Status Field Convention".
- Wajibkan enum untuk domain yang sudah stabil: Payroll, Loan, Invoice, Cooperative Payment, Vendor, Reward Redemption.
- Untuk factory/seeder, tetap gunakan enum value agar refactor aman.
- Tambahkan test hygiene yang mendeteksi magic string status di folder `app/`, dengan allowlist sementara bila perlu.

Prioritas: tinggi.

### 3. `AppServiceProvider::registerJobListeners()` Tampak Dead Code

Temuan ini valid berdasarkan kode saat ini.

Di `AppServiceProvider`, ada method:

```php
protected function registerJobListeners(): void
{
    Queue::failing(function (JobFailed $event) {
        (new FailedJobListener)->handle($event);
    });
}
```

Tetapi:

- method tidak dipanggil dari `boot()`;
- `Queue` tidak di-import;
- `JobFailed` tidak di-import;
- listener gagal job sudah ada sebagai `FailedJobListener`, tetapi wiring ini tampak tidak aktif.

Saran koreksi:

- Pilih salah satu: hapus method jika sudah diganti mekanisme lain, atau panggil dari `boot()` dan tambahkan import yang benar.
- Tambahkan test kecil untuk memastikan failed job listener benar-benar tercatat.

Prioritas: tinggi, karena ini bisa membuat tim mengira observability queue aktif padahal tidak.

### 4. Root Repo Berisi Artefak Yang Tidak Ideal

Temuan ini valid. Di root ada file seperti:

- `grep-count.txt`
- `harga.md`
- `presentasi.html`
- `rencana-pengembangan-sikopin.html`
- `s15-baseline.txt`
- `s15-detail.txt`
- `s15-detail2.txt`
- `s15-failures.txt`
- `s15-step1.txt`
- `s15-step2.txt`
- `s15-step3.txt`
- `s15-tail.txt`

Tidak semuanya otomatis salah. `presentasi.html` dan `rencana-pengembangan-sikopin.html` bisa saja materi bisnis. Tetapi root repo bukan tempat terbaik untuk artefak eksperimen, presentasi, atau output debugging.

Saran koreksi:

- Pindahkan materi presentasi/rencana ke `docs/` atau `docs/internal/`.
- Pindahkan output probe seperti `s15-*.txt` ke `artifacts/` atau hapus jika tidak dipakai.
- Tambahkan rule hygiene test untuk melarang file probe/debug tertentu masuk root.

Prioritas: sedang, tetapi cepat dibereskan.

### 5. Observability Belum Setara Dengan Target Arsitektur

`docs/architecture.md` menargetkan API response time < 500ms pada p95. Namun dokumen dan kode belum cukup menunjukkan pengukuran latency p95/p99 yang konsisten.

Saran koreksi:

- Tambahkan middleware pengukur response time.
- Log `route_name`, `method`, `status_code`, `duration_ms`, dan `request_id`.
- Untuk produksi, kirim metric ke backend yang persisten: Prometheus/OTLP/Influx, bukan hanya counter in-memory.
- Prioritaskan metric domain: payroll generation duration, failed webhook, failed notification outbox, queue failure, API p95.

Prioritas: sedang sampai tinggi untuk production readiness.

### 6. PPh21 Sebaiknya Menjadi Data, Bukan Konstanta Permanen

Kritik awal arahnya benar, tetapi perlu dibuat lebih presisi. Nilai pajak dan aturan compliance bisa berubah. Bila seluruh parameter PPh21 TER hardcoded, perubahan regulasi akan menjadi perubahan kode, bukan perubahan konfigurasi/data.

Saran koreksi:

- Simpan parameter pajak dalam tabel seperti `tax_brackets` atau `tax_rules`.
- Tambahkan `effective_from`, `effective_until`, dan `regulation_reference`.
- Test skenario lintas tahun dan resign mid-year.

Prioritas: sedang. Tidak harus hari ini, tetapi penting sebelum dipakai produksi untuk payroll nyata.

### 7. API Write Mobile Butuh Idempotency

Temuan ini masuk akal untuk Kojayaku/mobile. Endpoint write seperti POS transaction, payment proof, loan application, dan support ticket akan rentan duplikasi jika mobile retry saat koneksi buruk.

Saran koreksi:

- Terapkan header `Idempotency-Key` untuk endpoint write mobile yang berisiko finansial.
- Simpan hash request + response singkat di cache/database.
- Tambahkan test retry request dengan key yang sama tidak membuat data ganda.

Prioritas: tinggi untuk endpoint uang, sedang untuk endpoint non-finansial.

## Koreksi Terhadap Kritik Yang Terlalu Keras

### CI/CD

Kritik awal menyebut CI belum terlihat. Ini perlu dikoreksi: repo memiliki `.github/workflows/ci.yml` dan `.github/workflows/deploy.yml`. Kritik yang lebih tepat adalah:

- Pastikan CI benar-benar menjalankan Pint, PHPUnit, OpenAPI drift check, dan frontend build.
- Pertimbangkan menambahkan Larastan/PHPStan bila belum ada.
- Pastikan branch protection mewajibkan CI hijau.

### Optional Dependencies Frontend

`@rollup/rollup-linux-x64-gnu` dan `@tailwindcss/oxide-win32-x64-msvc` berada di `optionalDependencies`. Itu tidak otomatis salah. Kritik yang lebih tepat adalah:

- Pantau apakah `npm ci` di Linux/CI bersih tanpa warning yang mengganggu.
- Jangan pindahkan dependency tanpa bukti install/build bermasalah.

### UUID Untuk `Loan`

ADR-004 menyatakan UUID sebagai arah umum, tetapi beberapa model loan masih integer. Ini inkonsistensi valid, tetapi migrasi primary key loan bukan quick win.

Saran yang lebih aman:

- Jangan ubah primary key loan terburu-buru.
- Jika perlu non-guessable public identifier, tambahkan kolom `public_id` UUID/ULID untuk API exposure.
- Migrasi full UUID hanya dilakukan sebagai major database migration dengan rencana backfill dan compatibility.

### Browser/E2E Test

Kritik bahwa browser test belum ada masuk akal, tetapi jangan langsung memasang terlalu banyak skenario. Mulai dari 2-3 happy path bernilai tinggi:

- login admin -> generate payroll preview/generate flow;
- member login -> lihat dashboard Kojayaku;
- loan application -> approval -> repayment.

## Prioritas Tindak Lanjut

Status eksekusi per 3 Juni 2026: tiga item P0, tiga item P1, dan P2 scoped sudah dieksekusi. Detailnya dicatat di `docs/log.md`; keputusan arsitektur P0 dicatat di ADR-018, P1 di ADR-019, dan P2 di ADR-020.

| Prioritas | Tindakan | Alasan |
|---|---|---|
| P0 ✅ | Perbaiki/pastikan failed job listener aktif | Observability queue tidak boleh semu |
| P0 ✅ | Refactor `PayrollController::generate` ke service + transaction | Proses finansial harus atomic dan testable |
| P0 ✅ | Standarkan enum untuk status domain kritikal | Mengurangi bug runtime dan typo status |
| P1 ✅ | Tambahkan idempotency untuk mobile write endpoint finansial | Mencegah duplikasi akibat retry |
| P1 ✅ | Rapikan root artifacts | Hygiene repo dan onboarding developer |
| P1 ✅ | Tambahkan latency metric middleware | Membuktikan target p95, bukan sekadar klaim |
| P2 ✅ | Jadikan tax rule sebagai data | Siap menghadapi perubahan regulasi |
| P2 ⚠️ | Tambahkan PHPStan/Larastan level awal | Config awal sudah ada; dependency belum ditambah karena butuh approval |
| P2 ✅ | Tambahkan E2E smoke test | Smoke flow mobile API ke admin review sudah tercakup via PHPUnit |
| P3 | Evaluasi UUID/public id untuk Loan | Perlu rencana migrasi yang hati-hati |

## Rekomendasi ADR Baru

### ADR: Status Field Convention

Semua status domain wajib memakai enum setelah domainnya stabil. Magic string hanya boleh muncul di enum definition, migration constraint, atau allowlist test.

### ADR: Payroll Generation Boundary

Payroll generation adalah domain service, bukan controller logic. Semua proses payroll period harus atomic, idempotent, dan punya audit trail.

### ADR: Idempotency For Mobile Writes

Endpoint mobile yang membuat transaksi finansial wajib mendukung `Idempotency-Key`.

### ADR: Tax Rules As Data

Parameter pajak dan compliance tidak boleh dianggap permanen. Simpan sebagai data dengan masa berlaku.

### ADR: Production Metrics Contract

Target performa seperti p95 < 500ms harus punya instrumentasi resmi dan dashboard/alert.

## Kesimpulan

Saya setuju dengan inti kritik M3, tetapi setelah dikoreksi: proyek ini bukan "berantakan", melainkan sudah kuat secara fondasi dan perlu hardening pada beberapa area yang benar-benar penting.

Urutan yang paling rasional adalah:

1. Benahi hal yang bisa menyebabkan ilusi keamanan/observability, terutama failed job listener.
2. Refactor payroll generation karena menyentuh uang dan kepatuhan.
3. Standarkan enum/status agar bug kecil tidak menyebar.
4. Tambahkan idempotency untuk mobile write endpoint.
5. Rapikan artefak root dan observability produksi.

Dengan 2-3 sprint fokus pada area ini, KojayaPro/Kojayaku akan jauh lebih siap untuk production launch tanpa melakukan refactor besar yang tidak perlu.
