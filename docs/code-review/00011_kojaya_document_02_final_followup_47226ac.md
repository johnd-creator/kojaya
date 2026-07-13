# Final Senior Review Follow-up — Document 02 Payment & Reservation State Machine

## Verdict

**REQUEST CHANGES — belum boleh merge PR #3 dan belum boleh mulai Dokumen 03.**

Repository: `johnd-creator/kojaya`  
PR: `#3`  
Reviewed head: `47226aca875ce2f2b44c66017ab97fd34a1c158a`

## Yang sudah diterima

- canonical item membedakan customization;
- product snapshot dipertahankan;
- exact intent reuse guard;
- settlement enum fail closed;
- webhook amount minor-unit;
- reconciliation incident deduplication;
- PostgreSQL concurrency suite benar-benar berjalan;
- Generated Drift hijau;
- C1 memakai 32 workers;
- C5 menjalankan webhook → settlement;
- provider order ID intent deterministic per attempt;
- internal provider reference deterministic per attempt.

Masalah tersisa berada di jalur provider timeout dan recovery. Ini tetap P0 karena bisa menghasilkan duplicate charge atau pembayaran nyata tidak tersettlement.

---

# P0-1 — Provider Failure Ambigu Masih Membuka Attempt Baru

Pada `PaymentIntentChargeService`, semua `RuntimeException` provider masuk ke `handleChargeFailure()`, lalu:

```text
gateway_status → PENDING
attempt → FAILED
```

Ini menyamakan rejection definitif dengan timeout/read failure yang outcome-nya belum diketahui.

## Perbaikan wajib

Buat classification eksplisit:

```php
enum ProviderChargeOutcome
{
    case DefinitiveNotCreated;
    case DefinitiveRejected;
    case Unknown;
}
```

Untuk `Unknown`:

```text
intent tetap CHARGE_CREATING
attempt → UNKNOWN
provider_order_id tetap
idempotency_key tetap
buat/reuse reconciliation incident
blok attempt baru
```

Untuk `DefinitiveNotCreated`, retry attempt yang sama. Untuk `DefinitiveRejected`, mark failure sesuai contract provider.

## Tests

- provider mencatat charge lalu read-timeout;
- connection timeout ambigu;
- HTTP 4xx rejection;
- HTTP 5xx unknown;
- malformed/empty 2xx response.

---

# P0-2 — Recovery Mengklaim Retry Same Attempt, tetapi Membuat Attempt Baru

Recovery ketika provider menjawab not found melakukan:

```text
attempt → PREPARING
intent → PENDING
```

Namun `beginTransactionA()` untuk `PENDING` selalu:

```php
$nextAttempt = current charge_attempt + 1;
```

Jadi retry berikutnya membuat row attempt baru dan identity baru.

## Perbaikan wajib

Buat jalur khusus `retryExistingAttempt()` atau buat `beginTransactionA()` mengembalikan active attempt yang sama setelah authoritative not-found.

Retry wajib mempertahankan:

```text
attempt
idempotency_key
provider_order_id
```

## Tests

- attempt 1 → not found → retry → tetap attempt 1;
- tidak ada attempt 2;
- dua recovery workers hanya menghasilkan satu retry provider call.

---

# P0-3 — HTTP Provider Call Dilakukan di Dalam DB Transaction

`RecoverStaleChargeCreating::recoverIntent()` mengunci intent dan attempt, lalu memanggil HTTP provider sebelum commit.

## Risiko

- webhook tertahan;
- expiry worker tertahan;
- order polling tertahan;
- outage provider menjadi DB contention.

## Perbaikan wajib

Gunakan tiga fase:

1. **Phase A, short transaction:** lock, validate, claim reconciliation, snapshot identity, commit.
2. **Provider call:** di luar transaction.
3. **Phase B, short transaction:** lock ulang, verify claim/version, apply result bila masih authoritative.

Tambahkan bounded timeout dan stale-result fencing.

## Tests

- provider query ditahan barrier;
- webhook concurrent tidak deadlock;
- stale reconciliation response tidak overwrite PAID;
- dua recovery workers hanya satu active claim.

---

# P0-4 — Recovery Selalu Menulis PENDING Walau Provider Mengembalikan PAID/Terminal

Provider reconciliation dapat mengembalikan:

```text
PENDING
PAID
EXPIRED
CANCELLED
FAILED
```

Tetapi recovery saat ini selalu menulis:

```text
gateway_status = PENDING
```

## Perbaikan wajib

- `PENDING`: attach reference/payload, mark confirmed.
- `PAID`: lewat `MemberPaymentIntentStateService`, lalu settlement idempotent.
- `EXPIRED/CANCELLED/FAILED/DENIED`: lewat state service, release/expire reservation.
- unknown provider status: incident + blocked state, jangan fallback PENDING.

`reconcileIntentCharge()` juga tidak boleh memetakan status tak dikenal menjadi PENDING.

## Tests

- reconciliation PAID → PAID + CONSUMED + SETTLED + satu transaksi;
- reconciliation EXPIRED → reservation dilepas sekali;
- reconciliation terminal lain → state service path;
- unknown status → incident;
- amount mismatch → tidak settle.

---

# P0-5 — C6 dan C8 Belum Membuktikan Provider-Level Invariant

## C6

Unique reference dan one confirmed attempt belum membuktikan provider hanya dipanggil sekali, karena reference internal deterministic.

Gunakan shared fake provider dengan counter dan barrier.

Assert exact:

```text
provider create-call count === 1
attempt count === 1
confirmed attempt count === 1
```

## C8

Saat ini hanya membuat stale row lalu merace recovery dengan `ensureCharge()`.

Wajib simulasi:

```text
provider mencatat charge
response timeout
second request mencoba charge
recovery menemukan charge
late response dilepas
```

Assert:

```text
provider create-call count === 1
attempt count === 1
no orphan untracked
final state valid
```

Jangan gunakan assertion longgar seperti `<= 1` bila expected result exact.

---

# P1 — Jangan Sembunyikan Boost Route melalui `.gitignore`

Hapus:

```gitignore
resources/js/routes/boost/
```

Gunakan generator deterministic:

```bash
APP_ENV=testing php84 artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
```

---

# P1 — PR Cleanup

- Update PR body: C1 sekarang 32 workers, bukan 8.
- Revert file agent-memory/config yang tidak relevan, terutama `.commandcode/taste/taste.md`.
- Jangan mulai Dokumen 03.

---

# Required Verification

```bash
php84 artisan test --compact   tests/Unit/PaymentStateMachineTest.php   tests/Feature/PaymentCanonicalItemTest.php   tests/Feature/PaymentReservationStateMachineTest.php   tests/Feature/MemberStoreOrderApiTest.php

vendor/bin/phpunit   --configuration phpunit.pgsql.xml   tests/Feature/PaymentConcurrencyTest.php

composer84 install
npm ci --prefer-offline --no-audit
npm run build
APP_ENV=testing php84 artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
vendor/bin/pint --test
php84 artisan migrate:fresh --seed --force
php84 artisan test --compact --parallel --coverage --min=60
bin/openapi.sh check
git diff --check
git status
```

PostgreSQL output wajib:

```text
8 tests executed
0 skipped
0 incomplete
0 risky
```

---

# Acceptance Gate

Dokumen 02 baru diterima bila:

1. unknown outcome tidak reset PENDING;
2. unknown outcome tidak membuka attempt baru;
3. authoritative not-found retry memakai attempt yang sama;
4. retry memakai order ID dan idempotency key yang sama;
5. recovery HTTP di luar DB transaction;
6. recovery PAID masuk state service dan settlement;
7. recovery terminal masuk state service;
8. unknown provider status tidak fallback PENDING;
9. C6 provider call count tepat satu;
10. C8 benar-benar timeout + provider-created + late response;
11. Boost route tidak di-ignore;
12. seluruh GitHub Actions head final hijau;
13. PR body diperbarui;
14. PR tetap draft sampai senior approval;
15. jangan mulai Dokumen 03.

---

# Required Final Report

Laporkan:

1. commit SHA terbaru;
2. provider failure classification;
3. unknown/retry state diagram;
4. bukti same-attempt retry;
5. bukti provider call di luar transaction;
6. reconciliation behavior per status;
7. C6 call counter;
8. C8 timeout/late-response result;
9. PostgreSQL test output;
10. seluruh CI status;
11. working tree status.
