# Senior Review Follow-up — Document 02 Payment & Reservation State Machine

## Verdict

**REQUEST CHANGES — belum boleh merge dan belum boleh lanjut Dokumen 03.**

Repository: `johnd-creator/kojaya`  
Draft PR: `#3`  
Reviewed head: `dc44b4f2aead7919b255022a349668f4576f0131`

Perbaikan ini sudah menutup sebagian besar struktur dasar yang sebelumnya kurang:

- canonical item object;
- exact intent reuse guards;
- charge-attempt table;
- attempt fencing pada Transaction B;
- reconciliation incident;
- per-intent expiry processing;
- draft PR dan PostgreSQL CI wiring.

Namun acceptance Dokumen 02 masih gagal karena beberapa blocker berikut.

---

# P0-1 — Recovery Charge Masih Dapat Membuat Duplicate/Orphan Provider Charge

## Temuan

`RecoverStaleChargeCreating` masih melakukan:

```text
CHARGE_CREATING
→ attempt ditandai UNKNOWN
→ intent di-reset ke PENDING
→ request berikutnya memulai attempt baru
```

Komentar kode mengatakan “do NOT start a new attempt”, tetapi reset ke `PENDING` justru membuat `ensureCharge()` memulai attempt berikutnya.

`PaymentIntentChargeService::beginTransactionA()` juga melakukan pola serupa ketika menemukan stale `CHARGE_CREATING`:

1. attempt lama ditandai UNKNOWN;
2. intent diubah ke PENDING;
3. langsung membuat attempt baru.

Tidak ada provider reconciliation/query status yang benar-benar dijalankan.

Kolom berikut sudah dibuat tetapi belum menjadi mekanisme nyata:

```text
provider_order_id
request_payload
STATE_SENT
```

`provider_order_id` tidak dipersist sebelum provider call. Midtrans order ID masih random setiap provider call. Idempotency key memang stabil per attempt, tetapi recovery membuat attempt baru sehingga idempotency key ikut berubah.

## Dampak

Skenario berikut masih mungkin:

1. attempt 1 timeout di aplikasi;
2. provider sebenarnya sudah membuat charge;
3. recovery menandai UNKNOWN dan reset PENDING;
4. attempt 2 dibuat dengan idempotency key dan order ID baru;
5. provider memiliki dua charge aktif;
6. response attempt 1 datang terlambat.

Ini masih melanggar:

```text
1 intent → maksimum 1 active provider charge
```

## Perbaikan wajib

Untuk attempt UNKNOWN:

- **jangan ubah intent menjadi PENDING**;
- pertahankan state eksplisit, misalnya `CHARGE_UNKNOWN`, atau tetap `CHARGE_CREATING` dengan attempt state `UNKNOWN`;
- endpoint order harus mengembalikan `PREPARING/RECONCILIATION_REQUIRED`;
- blok attempt baru sampai reconciliation selesai.

Sebelum provider call:

1. generate stable provider order ID;
2. persist attempt, idempotency key, provider order ID, request payload/hash, state PREPARING;
3. commit;
4. ubah attempt ke SENT secara atomik sebelum/ketika dispatch.

Recovery harus:

1. lock intent + active attempt;
2. query provider menggunakan stable provider order ID/reference;
3. bila ditemukan, persist provider reference/presentation, mark CONFIRMED, set intent PENDING;
4. bila provider menyatakan not found secara authoritative, retry **attempt yang sama** dengan idempotency key dan provider order ID yang sama;
5. bila provider result ambigu/unavailable, tetap UNKNOWN, buat reconciliation incident, dan jangan membuat attempt baru.

Untuk internal/fake provider, implementasikan lookup/reconcile contract supaya behavior dapat dites deterministik.

---

# P0-2 — Stale Response Masih Ditandai CONFIRMED Walau Ditolak oleh Fencing

## Temuan

`commitTransactionB()` saat ini `void`.

Bila response berasal dari stale attempt, method hanya log lalu `return`. Setelah itu `ensureCharge()` tetap memanggil:

```php
markAttemptConfirmed(...)
```

Akibatnya attempt dapat ditandai `CONFIRMED` walaupun charge tidak pernah di-attach ke authoritative intent.

## Perbaikan wajib

Ubah Transaction B menjadi typed result, misalnya:

```php
ChargeCommitResult::Committed
ChargeCommitResult::StaleAttempt
ChargeCommitResult::InvalidReservation
ChargeCommitResult::Expired
ChargeCommitResult::Terminal
```

Hanya `Committed` yang boleh memanggil `markAttemptConfirmed`.

Untuk provider charge yang sudah tercipta tetapi hasilnya ditolak karena stale/invalid:

- jangan diam-diam discard;
- persist provider reference dan response pada attempt;
- mark `ORPHANED` atau `RECONCILIATION_REQUIRED`;
- buat reconciliation incident;
- blok automatic new charge sampai diselesaikan.

Tambahkan state attempt minimal:

```text
PREPARING
SENT
CONFIRMED
FAILED
UNKNOWN
ORPHANED
```

---

# P0-3 — PostgreSQL Concurrency CI Saat Ini False Positive / Ter-skip

## Temuan

`PaymentConcurrencyTest::setUp()` melakukan:

```php
$originalConnection = getenv('DB_CONNECTION') ?: 'sqlite';

if ($originalConnection !== 'pgsql') {
    markTestSkipped(...);
}
```

Tetapi `phpunit.xml` memiliki:

```xml
<env name="DB_CONNECTION" value="sqlite" force="true"/>
<env name="DB_DATABASE" value=":memory:" force="true"/>
```

Artinya environment PostgreSQL dari GitHub Actions dapat ditimpa menjadi SQLite sebelum test berjalan. Job PostgreSQL dapat hijau karena seluruh `PaymentConcurrencyTest` di-skip.

Status hijau bukan bukti C1–C8 dijalankan.

## Perbaikan wajib

Buat konfigurasi khusus:

```text
phpunit.pgsql.xml
```

atau ubah strategi env agar job PostgreSQL benar-benar memakai PostgreSQL.

CI wajib menjalankan command yang tidak dapat silent-skip:

```bash
php artisan test   --configuration phpunit.pgsql.xml   tests/Feature/PaymentConcurrencyTest.php
```

Tambahkan enforcement:

- test harus fail bila driver bukan pgsql pada job concurrency;
- jangan `markTestSkipped()` pada job PostgreSQL;
- output CI wajib menunjukkan delapan test C1–C8 benar-benar executed;
- fail job jika ada skipped test pada suite ini.

---

# P0-4 — Worker Harness Tidak Meneruskan Parameter Test dengan Benar

## Temuan

`writeWorkerScript($action, $params)` menghitung:

```php
$paramsJson = json_encode($params, ...);
```

tetapi nilai tersebut tidak digunakan oleh worker.

`startWorker()` justru mengirim:

```php
json_encode($this->sharedState, ...)
```

dan `$sharedState` tetap array kosong.

Saat concurrency suite benar-benar dieksekusi, worker tidak akan menerima:

```text
member_id
product_id
intent_id
gateway_reference
payload_file
```

## Perbaikan wajib

Gunakan salah satu:

```php
startWorker(
    workerFile,
    startFile,
    resultDir,
    label,
    params,
)
```

dan pass JSON params langsung sebagai argv, atau embed params immutable ke file worker.

Hapus `$sharedState` bila tidak diperlukan.

Tambahkan assertion sebelum action worker bahwa seluruh required params tersedia.

Process management juga wajib:

- timeout bounded;
- capture exit code;
- capture stdout/stderr;
- fail bila result file tidak ada;
- terminate worker yang hang;
- jangan hanya `proc_close()` tanpa deadline.

---

# P0-5 — C1–C8 Belum Membuktikan Acceptance Sebenarnya

## C1

Saat ini hanya 8 worker. Specification meminta 32 parallel requests.

Wajib membuktikan:

- 32 worker benar-benar start;
- 32 result diterima;
- seluruh successful response menunjuk intent sama;
- exactly one intent;
- exactly one reservation;
- exactly one `reservation.created` audit;
- jangan gunakan `<= 1`; harus `=== 1`.

## C4

PAID worker memanggil state service langsung. Pastikan test memverifikasi hasil dua worker, bukan hanya final combination.

Wajib assert:

- kedua worker benar-benar executed;
- satu authoritative outcome;
- bila expiry menang lalu PAID datang, reconciliation incident tercipta;
- bila PAID menang, settlement diproses exactly once;
- reservation/stock final sesuai outcome.

## C5

Worker hanya memanggil `MemberPaymentIntentStateService::applyGatewayEvent(...)`.

State service tidak menjalankan settlement/POS transaction, tetapi test mengharapkan `PosTransaction::count() === 1`.

Test harus meniru production flow:

```text
verify/apply webhook
→ bila PAID dan belum settled
→ MemberPaymentSettlementService::settle()
```

atau panggil endpoint webhook dengan verified fake provider.

Wajib assert:

- exactly one POS transaction;
- exactly one consume;
- exactly one outbox/notification;
- both worker results successful/idempotent.

## C6

Belum ada provider fake shared call counter dan controlled delay.

Provider internal terlalu cepat sehingga race tidak deterministik.

Wajib gunakan concurrency fake provider yang:

- menyimpan call count secara shared/durable;
- berhenti pada provider barrier;
- mengembalikan reference deterministik;
- membuktikan provider call count tepat `1`.

Response `PREPARING` tidak harus langsung memiliki provider reference. Test harus polling hingga reference authoritative tersedia.

## C8

Saat ini hanya membuat attempt stale secara manual, kemudian menjalankan recovery vs `ensureCharge`.

Ini belum mensimulasikan:

```text
provider membuat charge
→ aplikasi timeout
→ late response
→ recovery race
```

Wajib gunakan fake provider dengan skenario:

1. provider menerima attempt 1 dan mencatat charge;
2. response ditahan/timeout;
3. recovery berjalan;
4. request lain mencoba charge;
5. late response attempt 1 dilepas;
6. provider call count tetap 1;
7. tidak ada attempt 2 sebelum reconciliation;
8. charge attempt 1 terhubung kembali atau masuk incident;
9. tidak ada orphan yang tidak tercatat.

---

# P0-6 — Canonicalization Merusak Coffee Customization

## Temuan

`CanonicalOrderItem::canonicalise()` mengagregasi hanya berdasarkan `pos_product_id`.

Customization hanya diambil dari entry pertama:

```text
sugar_level
ice_level
cup_size
```

Contoh:

```text
Kopi A qty 1, sugar Normal
Kopi A qty 1, sugar Less
```

akan berubah menjadi satu line:

```text
Kopi A qty 2, sugar Normal
```

Order anggota menjadi salah.

## Perbaikan wajib

Pisahkan dua konsep:

### Canonical order lines

Key minimal:

```text
pos_product_id
normalized customization
unit-price snapshot
```

Dua line hanya boleh digabung bila product dan seluruh customization identik.

Customization harus:

- key sorted;
- nilai normalized;
- masuk fingerprint;
- masuk metadata authoritative;
- tetap utuh sampai settlement/CoffeeOrder.

### Reservation aggregate

Untuk lock/stock, aggregate quantity berdasarkan:

```text
pos_product_id + inventory location
```

Reservation aggregate boleh satu row per product, tetapi order line tidak boleh kehilangan customization.

Tambahkan test:

1. same product + same customization → boleh aggregate;
2. same product + different sugar → tetap dua order lines;
3. same product + different cup size → tetap dua order lines;
4. fingerprint berubah bila customization berubah;
5. settlement menghasilkan customization yang tepat.

---

# P0-7 — API Response Contract Kehilangan Product Snapshot

## Temuan

Controller sekarang membangun response dari `intent.metadata`, itu benar.

Namun canonical metadata hanya menyimpan:

```text
pos_product_id
quantity
unit_price
line_total
customization
reservation_location_id
```

Field `product` tidak lagi tersedia.

Pada coffee response:

```php
'item' => $items[0]['product'] ?? null
```

akan menjadi `null`.

Store/coffee item response juga kehilangan nama, gambar, kategori, SKU, dan snapshot presentasi lain yang sebelumnya tersedia.

## Perbaikan wajib

Jangan memakai request loser, tetapi pertahankan contract dengan salah satu desain:

1. simpan immutable `product_snapshot` pada canonical metadata; atau
2. rehydrate product untuk response menggunakan `pos_product_id`.

Preferred snapshot:

```text
product_snapshot:
- id
- name
- sku
- brand/variant
- image_url
- unit
```

Tambahkan API regression tests:

- `data.items[*].product` tersedia;
- `data.item` coffee tidak null;
- reused response identik dengan created response;
- loser request tidak mengubah product/customization response.

---

# P0-8 — Settlement Status Belum Fail Closed

## Temuan

`gatewayStatus()` dan `reservationStatus()` melempar `DomainException` untuk value invalid.

Tetapi `settlementStatus()` masih fallback:

```text
invalid settlement_status
→ bila settled_at null dianggap NOT_SETTLED
→ bila settled_at ada dianggap SETTLED
```

Ini bertentangan dengan claim “invalid enum/string throws DomainException”.

## Perbaikan wajib

Jika `settlement_status` non-null dan enum tidak dikenal:

```php
throw new DomainException(...)
```

Fallback hanya boleh dipakai untuk migration legacy yang benar-benar `null`, dan sebaiknya dipisahkan dalam explicit legacy-normalization path.

Tambahkan tests:

- invalid gateway → fail closed;
- invalid reservation → fail closed;
- invalid settlement → fail closed;
- state service tidak menulis apa pun ketika row corrupt.

---

# P1-1 — Existing Intent Harus Exact NOT_SETTLED

Komentar menyebut exact:

```text
PENDING + RESERVED + NOT_SETTLED
```

tetapi implementasi menerima `NOT_SETTLED` atau `SETTLING`.

`PENDING + SETTLING` sendiri adalah kombinasi ilegal.

Perbaiki menjadi exact `NOT_SETTLED` dan tambahkan regression test `PENDING + RESERVED + SETTLING` → 409.

---

# P1-2 — Webhook Amount Masih Menggunakan Float

Provider event masih meneruskan amount sebagai `float`, lalu memakai:

```php
round($providerAmount * 100)
```

Gunakan decimal string atau integer minor amount dari parser provider. Domain comparison wajib tanpa float conversion.

Tambahkan decimal-boundary tests.

---

# P1-3 — Reconciliation Incident Harus Idempotent dan Tahan Penghapusan Intent

Duplicate PAID mismatch/PAID-after-expiry dapat membuat incident OPEN berulang kali.

Migration juga memakai nullable foreign key tetapi `cascadeOnDelete()`, sehingga evidence reconciliation hilang bila intent dihapus.

Tambahkan event fingerprint/deduplication key, unique constraint, dan atomic `firstOrCreate`/upsert.

Gunakan `nullOnDelete()` atau restrict untuk evidence financial reconciliation. Test duplicate webhook menghasilkan satu incident.

---

# P1-4 — Notification/Outbox Masih Berada di Dalam Settlement Transaction

`MemberPaymentSettlementService` masih memanggil notification dispatcher dari dalam transaction.

Gunakan transactional outbox:

1. business transaction + outbox row atomik;
2. notification diproses setelah commit;
3. unique deduplication key secara DB constraint;
4. C5 assert exactly one outbox/notification.

---

# CI Blocker — Generated Drift Gagal

GitHub Actions PR #3 saat review:

```text
Generated Drift = FAILED
```

Failure terjadi pada:

```bash
git diff --exit-code resources/js/actions resources/js/routes
```

Jalankan dengan PHP 8.4:

```bash
npm ci --prefer-offline --no-audit
npm run build
php84 artisan wayfinder:generate
git diff -- resources/js/actions resources/js/routes
```

Review output lalu commit generated artifacts yang memang berasal dari perubahan controller.

Jangan menonaktifkan drift check.

---

# Required Verification

## Focused local

```bash
composer84 install
npm ci --prefer-offline --no-audit
npm run build

php84 artisan migrate:fresh --seed --force
vendor/bin/pint --test

php84 artisan test --compact   tests/Unit/PaymentStateMachineTest.php   tests/Feature/PaymentCanonicalItemTest.php   tests/Feature/PaymentReservationStateMachineTest.php   tests/Feature/MemberStoreOrderApiTest.php

php84 artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
bin/openapi.sh check
git diff --check
```

## PostgreSQL concurrency

Gunakan config PostgreSQL yang tidak ditimpa `phpunit.xml`.

Output wajib membuktikan:

```text
PaymentConcurrencyTest: 8 tests executed
0 skipped
0 incomplete
0 risky
```

C1 sendiri wajib menggunakan 32 worker.

## Full suite

```bash
php84 artisan test --compact --parallel --coverage --min=60
```

---

# Acceptance Gate

Dokumen 02 baru boleh diterima bila:

1. UNKNOWN attempt tidak di-reset ke PENDING.
2. UNKNOWN attempt tidak dapat memulai attempt baru.
3. Recovery melakukan provider reconciliation atau tetap blocked/manual.
4. Stable provider order ID dipersist sebelum call.
5. Retry attempt yang sama memakai order ID dan idempotency key yang sama.
6. Stale provider response tidak ditandai CONFIRMED secara salah.
7. Orphan provider charge selalu menghasilkan durable incident.
8. PaymentConcurrencyTest benar-benar berjalan PostgreSQL, bukan skip.
9. Worker params benar-benar diteruskan.
10. C1 memakai 32 worker dan exactly one audit.
11. C5 menjalankan full webhook→settlement flow.
12. C6 membuktikan provider call count tepat 1.
13. C8 benar-benar mensimulasikan timeout + provider-created charge + late response.
14. Coffee customization tidak tergabung lintas variant.
15. Product snapshot/API contract tidak hilang.
16. Invalid settlement status fail closed.
17. Reconciliation incident idempotent dan tidak cascade-delete.
18. Generated Drift hijau.
19. PHPUnit Parallel hijau.
20. Semua GitHub Actions pada head terbaru hijau.
21. PR tetap draft.
22. Jangan lanjut Dokumen 03.
23. Berhenti untuk senior re-review.

---

# Required Final Report

Setelah commit berikutnya, laporkan:

1. new commit SHA;
2. PR head SHA;
3. exact recovery state diagram;
4. provider reconciliation implementation;
5. stable order ID + idempotency evidence;
6. stale-response typed result behavior;
7. C1–C8 executed count dan zero skipped evidence;
8. provider fake call counts;
9. coffee customization tests;
10. API product snapshot regression tests;
11. reconciliation incident dedup test;
12. focused test results;
13. full suite + coverage;
14. migration results SQLite dan PostgreSQL;
15. Wayfinder/OpenAPI drift results;
16. seluruh GitHub Actions job status.
