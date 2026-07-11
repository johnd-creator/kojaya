# P2 Final Hardening Plan

P2 dikerjakan setelah seluruh blocker pada `01-P0-P1-BLOCKER-REMEDIATION-PLAN.md` selesai.

## P2-A — Encryption at Rest dan Blind Index

### Field prioritas

- `cooperative_members.identity_number`
- `cooperative_members.npwp`
- `cooperative_members.no_rekening`
- field rekening/identitas lain pada employee/vendor/customer bila memakai pola sama

### Desain

Gunakan field terenkripsi untuk nilai asli dan field hash terpisah untuk pencarian exact-match.

Contoh struktur:

```text
identity_number_enc
identity_number_bidx
npwp_enc
npwp_bidx
bank_account_enc
bank_account_bidx
```

Blind index:

```text
HMAC-SHA-256(normalized_value, dedicated_index_key)
```

Jangan menggunakan hash biasa tanpa secret key.

### Normalisasi

- NIK: hanya digit;
- NPWP: hanya digit;
- rekening: uppercase/trim dan digit sesuai domain;
- jangan mengubah nilai asli sebelum enkripsi selain canonical format yang disepakati.

### Tahapan migration

1. Tambahkan kolom encrypted dan blind index nullable.
2. Dual write pada create/update.
3. Backfill per batch dengan checkpoint.
4. Verifikasi count dan checksum.
5. Pindahkan read ke encrypted field.
6. Pindahkan exact search ke blind index.
7. Hentikan plaintext write.
8. Hapus plaintext hanya setelah backup, rollback plan, dan approval.
9. Dokumentasikan key rotation.

### Larangan

- jangan menyimpan encryption key di repository;
- jangan memakai `APP_KEY` sebagai satu-satunya index key tanpa versioning;
- jangan mencatat plaintext ke log/audit;
- jangan mendukung LIKE search pada PII terenkripsi;
- jangan mengembalikan plaintext hanya karena route admin lolos.

## P2-B — Inertia Data Contract

### Tujuan

Tidak ada controller yang mengirim model Eloquent mentah untuk domain sensitif.

### Buat DTO/resource khusus

- `CooperativeMemberListData`
- `CooperativeMemberDetailData`
- `CooperativeMemberEditData`
- `MemberResignationListData`
- `LoanListData`
- `LoanDetailData`

Setiap DTO mempunyai allowlist field berdasarkan permission.

### List page

List tidak perlu mengirim:

- identity number;
- NPWP penuh;
- rekening;
- address penuh;
- documents;
- payments;
- ledger entries;
- user model lengkap.

### Detail page

PII penuh hanya dikirim bila:

- user punya `view_cooperative_member_pii`;
- subject berada dalam organization scope;
- reason/purpose tersedia bila policy organisasi mensyaratkan;
- audit `member.pii.viewed` berhasil dicatat.

### Edit page

Daftar employee/user harus:

- di-scope organisasi;
- hanya mengirim id, display name, identifier minimum;
- tidak mengirim role, password, token, atau profile lain.

### Shared Inertia props

`auth.user` jangan berupa model mentah. Buat `AuthenticatedUserData` dengan:

- id;
- name;
- email;
- organization id/name minimum;
- roles/permissions yang diperlukan UI.

## P2-C — Audit Coverage dan Privacy

### Event minimum

- `member.pii.viewed`
- `member.pii.exported`
- `member.profile.updated`
- `member.account.linked`
- `member.account.unlinked`
- `member.status.transitioned`
- `member.access.revoked`
- `loan.approved`
- `loan.disbursed`
- `payment.approved`
- `payment.reconciled`
- `reservation.created`
- `reservation.consumed`
- `reservation.released`
- `reservation.expired`

### Audit contract

Semua event lewat `AuditLogService`; hindari `AuditLog::create()` langsung.

Field minimum:

- correlation_id;
- actor user id;
- actor roles snapshot;
- actor organization;
- subject;
- target organization;
- action;
- reason;
- occurred_at;
- request IP/user agent;
- result success/failure jika relevan.

### PII redaction

Redact minimal:

- password;
- token;
- secret;
- authorization;
- QR payload;
- identity_number;
- NIK;
- NPWP;
- bank account;
- card/account identifiers;
- document payload.

Audit boleh menyimpan:

- last four;
- normalized value hash;
- changed/not-changed flag;
- field names;
- masked values.

Jangan membuat test yang mengharapkan PII plaintext tersimpan di audit.

## P2-D — Reservation dan Idempotency Finalization

### Reservation state machine

Tambahkan state eksplisit pada tabel/metadata:

- RESERVED
- CONSUMED
- RELEASED
- EXPIRED

Idealnya gunakan tabel reservation terpisah bila order bertambah kompleks.

### Atomicity

`reserve`, `consume`, dan `release` harus:

- berjalan dalam transaction;
- lock intent/reservation row;
- lock inventory stock row;
- idempotent pada retry;
- menghasilkan satu audit event.

### Expiry worker

Command/job terjadwal:

```text
orders:expire-reservations
```

Memproses intent:

- status PENDING;
- expires_at <= now;
- belum consumed/released.

Worker harus:

- lock batch dengan `skip locked` bila DB mendukung;
- release reserved stock;
- set intent EXPIRED;
- aman dijalankan ulang.

### Charge reuse

Jangan reuse charge bila:

- `expires_at <= now`;
- provider status terminal;
- artifact pembayaran kosong;
- channel berubah;
- amount berubah.

Jangan menimpa expiry order 30 menit menjadi 1 hari tanpa kebijakan eksplisit.

### Exception handling

Catch duplicate-key error berdasarkan SQL state/constraint name. Jangan menangkap semua `QueryException` sebagai idempotency duplicate.

## P2-E — Pagination dan Query Budget

### Standard

- default `15`;
- minimum `1`;
- maximum `50` untuk mobile umum;
- maximum `100` hanya endpoint admin yang dibenarkan;
- export tidak memakai pagination, tetapi memakai queue/streaming dan batas scope.

### Cakupan

Audit semua controller:

- Member API;
- Cooperative API;
- ESS;
- technician;
- notification;
- audit log;
- report;
- POS;
- procurement;
- certificates/MCU.

### Architecture test

Cari seluruh pemakaian:

```php
paginate($request->integer('per_page'
simplePaginate($request->integer('per_page'
limit($request->integer(
```

Pastikan memakai centralized resolver atau explicit bounded value.

## P2-F — Granular Sanctum Ability Cutover

### Tahapan

1. Tambahkan granular abilities.
2. Ubah route admin per domain.
3. Tambahkan compatibility telemetry untuk legacy ability use.
4. Rotate/revoke token lama.
5. Hapus issuance `cooperative:read/write`.
6. Hapus route enforcement legacy.
7. Tambahkan contract test agar route baru tidak memakai legacy ability.

Contoh:

```text
cooperative.member.read
cooperative.member.write
cooperative.member.verify
cooperative.member.approve
cooperative.member.export
cooperative.resignation.review
cooperative.loan.read
cooperative.loan.review
cooperative.loan.approve
cooperative.payment.record
cooperative.ledger.read
```
