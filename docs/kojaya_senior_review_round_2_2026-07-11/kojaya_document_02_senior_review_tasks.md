# Senior Review Task — Document 02 Payment & Reservation State Machine

## Status

**REQUEST CHANGES — Document 02 belum diterima.**

Repository: `johnd-creator/kojaya`  
Branch: `remediation/document-02-payment-reservation`  
Reviewed head: `dc7c35b09ded4ead8ef7f5970ad24dea55a2aeda`  
Authoritative specification:

```text
docs/kojaya_senior_review_round_2_2026-07-11/02-PAYMENT-RESERVATION-STATE-MACHINE.md
```

Tetap hanya mengerjakan Dokumen 02. Jangan mulai Dokumen 03, jangan merge ke `main`, dan jangan melakukan refactor yang tidak berkaitan dengan payment/reservation.

Implementasi saat ini memiliki fondasi yang baik—enum, central intent service, charge service, state service, settlement guard, expiry worker, dan test dasar—tetapi acceptance belum lulus karena masih ada pelanggaran invariant dan test concurrency belum benar-benar concurrent.

---

# P0-1 — Perbaiki Canonical Item dan Korupsi Reservation Metadata

## Masalah

Pada:

```text
app/Services/Cooperative/MemberOrderReservationService.php
```

`canonicaliseItems()` sudah menggabungkan duplicate product dan menghasilkan quantity agregat. Namun setelah reserve, service kembali melakukan loop atas `$items` asli dan memasukkan canonical entry yang sama untuk setiap duplicate item.

Contoh input:

```php
[
    ['pos_product_id' => 10, 'quantity' => 3],
    ['pos_product_id' => 10, 'quantity' => 2],
]
```

Stock di-reserve `5`, tetapi metadata dapat berisi dua entry product 10 yang masing-masing quantity `5`.

Akibatnya saat release/consume:

- reserved stock dapat dikurangi `10`, padahal order hanya reserve `5`;
- `max(..., 0)` dapat menghapus reservation milik order lain;
- settlement dapat membuat item transaksi dengan quantity ganda;
- fingerprint, reservation, response, dan settlement tidak memakai satu canonical representation.

## Perbaikan wajib

1. Canonicalize item **sekali** sebelum fingerprint, reserve, metadata, response, dan settlement.
2. Hasil canonical harus memiliki tepat satu row per `pos_product_id`.
3. Sort ascending berdasarkan `pos_product_id`.
4. Snapshot authoritative minimal per row:
   - `pos_product_id`;
   - aggregated `quantity`;
   - `unit_price` normalized;
   - `line_total`;
   - `reservation_location_id`;
   - data customization yang benar-benar memengaruhi produk/order.
5. `MemberOrderIntentService` tidak boleh menerima dua sumber item yang bisa berbeda:
   - hapus parameter `$items` terpisah; atau
   - buat typed canonical request/value object dan gunakan objek yang sama untuk fingerprint + reserve + metadata.
6. Settlement dan release wajib menggunakan metadata canonical dari DB, bukan request awal.

## Regression tests wajib

Tambahkan test berikut:

1. Duplicate product `3 + 2`:
   - reserved hanya `5`;
   - metadata hanya satu row quantity `5`;
   - settlement membuat transaksi quantity `5`, bukan `10`.
2. Dua order aktif untuk produk sama:
   - order A reserve `5`;
   - order B reserve `4`;
   - release order A;
   - reserved akhir harus `4`, tidak boleh `0`.
3. Duplicate items dengan urutan berbeda menghasilkan fingerprint yang sama.
4. Input satu row quantity `5` dan dua row quantity `3 + 2` dianggap canonical payload yang sama.

---

# P0-2 — Tambahkan Attempt Fencing dan Hilangkan Risiko Orphan/Duplicate Provider Charge

## Masalah

Pada:

```text
app/Services/Integrations/PaymentIntentChargeService.php
app/Console/Commands/RecoverStaleChargeCreating.php
```

Transaction B hanya memeriksa:

```php
gateway_status === 'CHARGE_CREATING'
```

tetapi tidak memeriksa bahwa provider response berasal dari `charge_attempt` yang masih aktif.

Race yang saat ini mungkin terjadi:

1. attempt 1 menjadi `CHARGE_CREATING`;
2. provider lambat tetapi sebenarnya berhasil membuat charge;
3. recovery job mengubah intent secara buta menjadi `PENDING`;
4. request lain memulai attempt 2;
5. response attempt 1 tiba ketika DB sedang `CHARGE_CREATING` untuk attempt 2;
6. response attempt 1 dapat disimpan sebagai hasil attempt 2;
7. provider attempt 2 juga mungkin sudah membuat charge.

Ini melanggar invariant:

```text
1 intent -> maksimum 1 active provider charge
```

Recovery job saat ini juga hanya mengubah stale `CHARGE_CREATING` menjadi `PENDING` tanpa query/reconciliation terhadap provider.

## Perbaikan wajib

Implementasikan attempt fencing yang eksplisit.

Minimum:

1. Transaction A menghasilkan `attempt` atau token/version yang immutable untuk satu provider call.
2. Transaction B menerima expected attempt:
   ```php
   commitTransactionB($intentId, $expectedAttempt, $charge)
   ```
3. Transaction B wajib memverifikasi semuanya:
   - `gateway_status === CHARGE_CREATING`;
   - `charge_attempt === expectedAttempt`;
   - reservation masih `RESERVED`;
   - intent belum expired;
   - intent belum paid/settled/terminal.
4. Failure handler juga wajib memverifikasi `charge_attempt === expectedAttempt`.
5. Response dari stale attempt tidak boleh menimpa attempt baru.
6. Jangan menaikkan attempt baru selama hasil attempt lama masih `UNKNOWN`.

## Recovery design

Jangan lagi melakukan blind reset:

```php
CHARGE_CREATING -> PENDING
```

Pilih desain aman:

### Preferred

Buat durable charge-attempt/outbox record, misalnya:

```text
member_payment_charge_attempts
- id
- member_payment_intent_id
- attempt
- idempotency_key
- state: PREPARING/SENT/CONFIRMED/FAILED/UNKNOWN
- provider_reference
- request_payload_hash
- response_payload
- started_at
- completed_at
```

Dengan constraint unique pada:

```text
(member_payment_intent_id, attempt)
idempotency_key
provider_reference bila tidak null
```

Recovery harus:

1. lock intent dan active attempt;
2. query provider berdasarkan stable order/reference/idempotency identity bila provider mendukung;
3. jika provider charge ditemukan, persist charge yang sama;
4. jika status benar-benar tidak ditemukan dan retry aman, retry menggunakan **idempotency key yang sama**, bukan langsung membuat attempt baru;
5. bila status ambigu, tandai `UNKNOWN`/reconciliation required—jangan membuat charge kedua.

### Minimal acceptable

Bila belum membuat table attempt:

- simpan active attempt token dan stable provider order ID sebelum provider call;
- retry stale operation memakai identity dan idempotency key yang sama;
- Transaction B dan failure handler memakai compare-and-set atas attempt;
- recovery tidak boleh memulai attempt baru sampai provider reconciliation selesai.

## Provider identity

Saat ini idempotency key memakai attempt, tetapi provider order ID dibuat random. Pastikan satu logical attempt memiliki:

- stable idempotency key;
- stable provider order ID;
- keduanya dapat dipakai ulang oleh recovery/retry.

## Tests wajib

1. Slow attempt 1 + recovery + attempt 2:
   - stale attempt 1 tidak boleh menimpa attempt 2.
2. Provider timeout setelah provider sebenarnya membuat charge:
   - retry/recovery tidak membuat charge kedua.
3. Failure callback attempt lama tidak boleh mengubah attempt baru.
4. Provider fake call count harus tepat `1` untuk charge race yang sama.
5. Active provider reference pada intent harus tepat satu.

---

# P0-3 — Existing Intent Hanya Boleh Direuse dalam State PENDING + RESERVED

## Masalah

Pada:

```text
app/Services/Cooperative/MemberOrderIntentService.php
```

`validateExisting()` memeriksa terminal status, expiry, amount, channel, dan fingerprint, tetapi tidak mensyaratkan:

```text
gateway=PENDING
reservation=RESERVED
settlement=NOT_SETTLED
```

Intent `PENDING + RELEASED`, `PENDING + NONE`, atau state lain yang tidak punya reservation aktif masih dapat direuse dan diteruskan ke charge creation.

## Perbaikan wajib

Reuse hanya boleh bila seluruh invariant terpenuhi:

```text
gateway_status = PENDING
reservation_status = RESERVED
settlement_status = NOT_SETTLED
settled_at = null
expires_at > now
request_fingerprint exact match
amount exact match
channel exact match
```

`CHARGE_CREATING` boleh menghasilkan typed `Preparing`, bukan dianggap reusable order biasa.

State lain harus menghasilkan `409 Conflict`.

Legacy intent dengan `request_fingerprint = null` tidak boleh direuse secara fail-open. Pilih salah satu:

- backfill fingerprint dari metadata authoritative yang lengkap; atau
- fail closed dan minta client memakai `client_reference` baru.

## Response authoritative

Pada controller store/coffee, response reused intent harus selalu dibentuk dari:

```text
intent.metadata
intent.amount
intent.channel
intent.expires_at
intent gateway payload
```

Jangan memakai `fulfillment_method`, `pickup_location`, notes, atau items dari request loser.

Tambahkan seluruh field yang memengaruhi order ke canonical fingerprint, termasuk:

- pickup location bila memengaruhi fulfillment;
- fulfillment method;
- customization;
- notes hanya bila memang bagian contractual order;
- aggregated items;
- server-side unit-price snapshot.

## Tests wajib

1. PENDING + RELEASED dengan same key harus 409 dan tidak membuat charge.
2. PENDING + NONE harus 409.
3. CHARGE_CREATING menghasilkan Preparing/retry response, bukan reserve/charge baru.
4. Legacy null fingerprint fail closed.
5. Same key dengan pickup location berbeda harus conflict.
6. Response loser selalu sama dengan metadata winner.

---

# P0-4 — Jangan Persist Kombinasi State Ilegal pada Webhook PAID

## Masalah

Pada:

```text
app/Services/Integrations/MemberPaymentIntentStateService.php
```

Bila webhook `PAID` datang ketika reservation bukan `RESERVED`, service tetap menyimpan:

```text
gateway_status = PAID
```

sementara reservation bisa `RELEASED` atau `EXPIRED`.

Itu langsung melanggar invariant Dokumen 02:

```text
PAID tidak boleh coexist dengan EXPIRED/RELEASED
```

Kemudian settlement hanya menandai `settlement_status=FAILED`, sehingga record tetap berada dalam kombinasi state ilegal.

## Perbaikan wajib

Verified provider PAID tetap harus dicatat sebagai fakta, tetapi jangan merusak authoritative intent state.

Implementasikan durable reconciliation event/incident:

- simpan verified webhook/event secara immutable;
- simpan provider status, amount, reference, signature verification result, event timestamp;
- buat reconciliation incident dengan status OPEN;
- jangan create POS transaction;
- jangan release/consume stock lagi;
- authoritative intent harus tetap dalam kombinasi state yang secara eksplisit valid.

Pilih desain state yang eksplisit dan konsisten. Jangan hanya log/audit lalu menyimpan kombinasi ilegal.

`isStateCombinationValid()` harus mencakup setidaknya:

- non-PAID terminal + CONSUMED = invalid;
- PAID + RELEASED/EXPIRED = invalid;
- SETTLED tanpa PAID = invalid;
- SETTLED tanpa CONSUMED untuk order = invalid;
- CONSUMED tanpa PAID untuk order = invalid;
- SETTLING tanpa PAID = invalid.

Invalid enum/string value harus fail closed atau melempar domain exception. Jangan fallback otomatis menjadi PENDING/NONE.

## Webhook amount validation

Sebelum menerima PAID:

1. gunakan amount dari parsed signed provider event;
2. bandingkan dengan authoritative intent amount dalam minor unit/integer;
3. bila mismatch:
   - jangan settlement;
   - buat reconciliation incident;
   - audit;
   - return acknowledged response yang aman agar provider tidak melakukan retry storm, sesuai kebijakan integrasi.

Validasi juga provider reference dan channel bila tersedia.

## Tests wajib

1. Signed PAID dengan amount berbeda:
   - tidak settle;
   - tidak consume;
   - incident tercatat.
2. PAID setelah reservation expired/released:
   - tidak membuat kombinasi state ilegal;
   - tidak membuat transaksi;
   - incident tercatat.
3. Invalid status string tidak dianggap PENDING.
4. Seluruh matrix kombinasi gateway/reservation/settlement diuji.

---

# P0-5 — Perbaiki SKIP LOCKED Expiry Worker dan Metrik

## Masalah

Pada:

```text
app/Console/Commands/ExpireMemberOrderReservations.php
```

Query menggunakan:

```php
lockForUpdate()->skipLocked()->get()
```

tanpa explicit transaction yang melingkupi SELECT dan processing. Pada PostgreSQL autocommit, row lock selesai ketika statement SELECT selesai, sebelum loop memproses intent.

Akibatnya `SKIP LOCKED` tidak memberikan proteksi yang dimaksud.

Selain itu:

- `skipped_locked` dinaikkan untuk semua hasil `false`, bukan row yang benar-benar diskip karena lock;
- row yang benar-benar di-skip tidak pernah ada dalam result set sehingga tidak bisa dihitung dengan cara tersebut;
- query hanya memilih `PENDING`, sehingga `skipped_paid` praktis tidak mewakili paid race secara akurat.

## Perbaikan wajib

Gunakan salah satu pola:

### Per-row transaction

- ambil candidate IDs tanpa lock;
- untuk setiap ID, mulai transaction;
- re-read `FOR UPDATE SKIP LOCKED`/conditional lock;
- revalidate seluruh state;
- transition + release dalam transaction yang sama.

### Batch claim

- claim bounded rows secara atomik dengan explicit processing token/state;
- commit claim;
- process claimed rows idempotently;
- finalize state.

Pastikan:

- no long transaction untuk seluruh batch;
- state service tetap authoritative;
- paid-versus-expiry race menghasilkan satu valid outcome;
- metric memiliki definisi yang benar.

Metric minimum:

```text
candidates
claimed
expired
skipped_state_changed
skipped_not_due
failed
```

Jangan melaporkan `skipped_locked` kecuali benar-benar dapat diukur.

Tambahkan `withoutOverlapping()` juga untuk stale-charge recovery schedule, atau buat recovery sendiri aman terhadap parallel invocation.

## Tests wajib

1. Dua expiry workers berjalan paralel:
   - intent hanya diproses sekali;
   - reserved stock hanya dilepas sekali.
2. PAID webhook versus expiry dengan barrier:
   - satu hasil valid;
   - tidak pernah PAID+EXPIRED/RELEASED.
3. Dua recovery workers paralel:
   - satu active recovery;
   - tidak membuat attempt/charge ganda.

---

# P0-6 — Ganti “Concurrency Tests” Serial Menjadi True PostgreSQL Concurrency Tests

## Masalah

File:

```text
tests/Feature/PaymentConcurrencyTest.php
```

saat ini bukan concurrency test:

- C1 memakai loop serial 5 kali;
- C2 serial;
- C4 webhook kemudian expiry secara berurutan;
- C5 webhook berulang secara berurutan;
- C6 dua pemanggilan `ensureCharge()` secara berurutan;
- C7 hanya satu order, bukan dua request dengan opposite ordering;
- C8 hanya mengubah status manual lalu menjalankan recovery.

Dokumen authoritative mewajibkan true PostgreSQL/MySQL race.

Selain itu GitHub Actions PostgreSQL job saat ini tidak menjalankan `PaymentConcurrencyTest.php`.

## Perbaikan wajib

Buat true concurrency harness PostgreSQL menggunakan:

- separate processes/connections;
- barrier synchronization;
- worker command/script yang menerima DB credentials test;
- deterministic start signal;
- bounded timeout;
- hasil worker ditulis ke temp result files atau DB test table;
- tidak menggunakan SQLite untuk claim concurrency coverage.

Implementasikan C1–C8 sesuai dokumen:

### C1

32 parallel same-key same-payload requests:

- one intent;
- one reservation;
- one provider call;
- same returned intent;
- one `reservation.created` audit.

### C2

Parallel same-key different-payload:

- one winner;
- loser 409/conflict;
- winner metadata authoritative;
- reservation sesuai winner.

### C3

Settled key reuse:

- conflict;
- no new reservation/charge.

### C4

Barrier-synchronized PAID versus expiry:

- exactly one valid path;
- no illegal combination.

### C5

Parallel duplicate PAID webhooks:

- one business transaction;
- one consume;
- one notification/deduplication record.

### C6

Parallel charge calls:

- provider fake shared call count exactly one;
- one provider reference.

### C7

Two concurrent orders with opposite item ordering:

- no deadlock;
- correct reservation totals;
- no over-reservation.

### C8

Provider timeout/late response/recovery race:

- stable idempotency identity;
- no orphan charge;
- no stale attempt overwrite;
- recoverable state.

## CI wajib

Update PostgreSQL job agar menjalankan:

```bash
php artisan test --compact \
  tests/Feature/Cooperative/PosTransactionConcurrencyTest.php \
  tests/Feature/Cooperative/MemberLifecycleConcurrencyTest.php \
  tests/Feature/PaymentConcurrencyTest.php
```

Jika PaymentConcurrencyTest memiliki non-PostgreSQL logical tests, pisahkan:

```text
PaymentStateMachineFeatureTest.php
PaymentPostgresConcurrencyTest.php
```

PostgreSQL test harus fail/skip dengan alasan eksplisit bila DB bukan PostgreSQL, dan wajib PASS di CI PostgreSQL.

---

# P1-1 — State Machine Harus Menjadi Enforcement, Bukan Hanya Helper

## Masalah

`isStateCombinationValid()` saat ini hanya helper boolean dan tidak dipanggil sebelum seluruh write.

Beberapa service masih menulis `gateway_status`, `reservation_status`, dan `settlement_status` secara langsung.

## Perbaikan

1. Centralize domain transition API.
2. Semua transition melakukan:
   - lock;
   - expected-current-state check;
   - target combination validation;
   - write;
   - audit.
3. Hapus atau batasi public path yang dapat melakukan direct status write.
4. `markSettled()` wajib memvalidasi:
   - gateway PAID;
   - order reservation CONSUMED;
   - settlement SETTLING/NOT_SETTLED;
   - belum settled.
5. Tambahkan DB constraints bila portable, atau migration validation barrier bila SQLite compatibility membatasi check constraints.
6. Gunakan enum casts atau strict accessor yang tidak fail-open.

---

# P1-2 — Gunakan Representasi Uang yang Deterministik

Hindari float untuk fingerprint, equality, dan gateway comparison.

Gunakan salah satu:

- integer minor units; atau
- normalized decimal string dengan fixed scale.

Perbaiki:

- amount comparison;
- unit price snapshot;
- line total;
- webhook gross amount;
- fingerprint payload.

Tidak boleh bergantung pada tolerance float `0.005` untuk idempotency identity.

Tambahkan tests untuk:

- decimal boundary;
- `10000`, `10000.0`, dan `"10000.00"` menghasilkan canonical amount yang sama;
- nominal berbeda satu minor unit menghasilkan conflict.

---

# P1-3 — Notification Setelah Commit / Transactional Outbox

Dokumen menyatakan notification dilakukan setelah commit.

Saat ini settlement memanggil notification dispatcher di dalam transaction. Dispatcher memang menulis database notification dan memiliki deduplication key, tetapi tetap rapikan boundary:

- business state dan outbox event dibuat atomik;
- pengiriman/dispatch diproses setelah commit;
- deduplication key memiliki unique enforcement atau atomic insert, bukan check-then-create saja.

Test C5 harus membuktikan tepat satu notification/outbox event.

---

# P1-4 — Migration dan Legacy Rollout

Migration baru hanya menambah nullable fingerprint dan default settlement status.

Tambahkan rollout policy:

1. Audit existing open order intents.
2. Backfill hanya jika canonical metadata cukup untuk menghasilkan fingerprint yang benar.
3. Jika metadata tidak cukup, tandai non-reusable/manual review.
4. Jangan reuse null fingerprint.
5. Validasi existing state combinations sebelum memperketat constraint.
6. Pastikan rollback migration aman.
7. Test `migrate:fresh --seed` pada SQLite dan PostgreSQL.

---

# P1-5 — PR dan Remote CI Protocol

Saat review dilakukan, branch sudah dipush tetapi belum memiliki pull request dan commit ini belum memiliki GitHub Actions run.

Buat draft PR:

```bash
gh pr create \
  --repo johnd-creator/kojaya \
  --base main \
  --head remediation/document-02-payment-reservation \
  --draft \
  --title "feat(payment): enforce payment and reservation state machine" \
  --body-file <prepared-pr-body.md>
```

PR body wajib berisi:

- scope Dokumen 02;
- invariants;
- migration;
- backward compatibility;
- concurrency evidence;
- known operational/reconciliation behavior;
- exact tests;
- no claim “complete” sebelum CI remote hijau.

---

# Required Verification

Gunakan PHP 8.4 untuk Kojaya:

```bash
composer84 install
npm ci --prefer-offline --no-audit
npm run build
APP_ENV=testing php84 artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
php84 artisan migrate:fresh --seed --force
vendor/bin/pint --test
```

Focused tests:

```bash
php84 artisan test --compact \
  tests/Unit/PaymentStateMachineTest.php \
  tests/Feature/PaymentReservationStateMachineTest.php
```

PostgreSQL concurrency harus dijalankan melalui CI job dan menghasilkan PASS, bukan logical SQLite simulation.

Full suite:

```bash
php84 artisan test --compact --parallel --coverage --min=60
bin/openapi.sh check
git diff --check
git status
```

---

# Acceptance Criteria

Dokumen 02 baru boleh dinyatakan selesai bila semuanya terpenuhi:

1. Duplicate product tidak menghasilkan duplicate aggregate metadata.
2. Release/consume tidak dapat mengurangi reservation milik order lain.
3. Fingerprint, reserve, metadata, response, dan settlement memakai canonical item yang sama.
4. Existing intent hanya reusable pada exact `PENDING + RESERVED + NOT_SETTLED`.
5. Legacy null fingerprint fail closed atau dibackfill secara aman.
6. Transaction B dan failure callback memakai attempt fencing.
7. Stale recovery tidak melakukan blind reset dan tidak dapat membuat orphan/duplicate provider charge.
8. Stable idempotency key dan provider order identity terbukti melalui timeout/retry test.
9. Signed webhook amount mismatch tidak dapat settlement.
10. PAID-after-release/expiry tercatat sebagai durable reconciliation incident tanpa persisted illegal state.
11. Expiry worker benar-benar memakai transactional locking/claiming.
12. C1–C8 adalah true PostgreSQL concurrency tests.
13. Payment concurrency tests dijalankan di GitHub Actions PostgreSQL job.
14. One paid intent menghasilkan tepat satu business transaction, consume, dan notification/outbox event.
15. Full local suite lulus.
16. Seluruh GitHub Actions pada PR head terbaru hijau.
17. Working tree bersih.
18. Tidak ada perubahan di luar scope Dokumen 02.
19. Jangan mulai Dokumen 03.
20. Berhenti untuk senior review sebelum merge.

---

# Required Final Report

Setelah perbaikan, laporkan:

1. commit SHA terbaru;
2. draft PR URL;
3. daftar file yang berubah;
4. desain canonical item final;
5. desain charge-attempt fencing final;
6. perilaku recovery saat provider result UNKNOWN;
7. desain reconciliation incident untuk PAID-after-expiry/release dan amount mismatch;
8. hasil C1–C8 dengan bukti PostgreSQL;
9. provider fake call counts;
10. reservation and transaction quantities pada duplicate-product tests;
11. hasil focused tests;
12. hasil full suite dan coverage;
13. hasil migration/seed SQLite dan PostgreSQL;
14. hasil GitHub Actions setiap job;
15. residual risk yang masih ada.

Jangan mengklaim Dokumen 02 selesai hanya berdasarkan test SQLite atau test serial.
