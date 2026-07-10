# Matriks Test Otorisasi

## Tujuan

Matriks ini menjadi baseline test untuk memastikan role anggota dan admin koperasi tidak memperoleh akses melebihi tanggung jawabnya.

Setiap fitur sensitif minimal memiliki:

- satu positive test;
- satu unauthenticated test;
- satu wrong-role test;
- satu cross-member atau cross-organization test;
- satu invalid-state test;
- audit assertion untuk state transition.

## Akun dan Fixture Minimum

Siapkan fixture berikut:

| Fixture | Role | Status Member | Organisasi |
|---|---|---|---|
| `activeMemberA` | Anggota | ACTIVE | Org A |
| `activeMemberB` | Anggota | ACTIVE | Org A |
| `pendingMemberA` | none/Anggota sesuai flow | PENDING | Org A |
| `revisionMemberA` | none/Anggota sesuai flow | REVISION | Org A |
| `rejectedMemberA` | none | REJECTED | Org A |
| `inactiveMemberA` | Anggota/none | INACTIVE | Org A |
| `resignedMemberA` | Anggota/none | RESIGNED | Org A |
| `adminA` | Admin Koperasi | n/a | Org A |
| `managerA` | Manajer Koperasi | n/a | Org A |
| `boardA` | Pengurus Koperasi | n/a | Org A |
| `cashierA` | Kasir Koperasi | n/a | Org A |
| `adminB` | Admin Koperasi | n/a | Org B |
| `systemAdmin` | System Admin | n/a | Pusat |

Data finansial harus tersedia untuk Member A dan Member B agar cross-owner test benar-benar bermakna.

## 1. Login dan Token Abilities

| Case | Actor | App | Expected |
|---|---|---|---|
| TOKEN-001 | activeMemberA | `member` | token hanya berisi profile dan active-member abilities |
| TOKEN-002 | pendingMemberA | `member` | token hanya berisi onboarding abilities; tidak ada financial write |
| TOKEN-003 | revisionMemberA | `member` | onboarding/revision abilities saja |
| TOKEN-004 | rejectedMemberA | `member` | tidak ada active-member abilities; login dapat ditolak sesuai keputusan produk |
| TOKEN-005 | resignedMemberA | `member` | tidak ada active-member abilities |
| TOKEN-006 | adminA | `cooperative-admin` | abilities sesuai permission Admin Koperasi |
| TOKEN-007 | cashierA | `cooperative-admin` | tidak memperoleh member management atau loan approval ability |
| TOKEN-008 | systemAdmin | `member` | tidak memperoleh wildcard `*` |
| TOKEN-009 | systemAdmin | app tidak dikenal | request ditolak 422 atau memperoleh safe default, bukan wildcard |
| TOKEN-010 | user tanpa cooperativeMember | `member` | tidak memperoleh `member:read/write` |

**Assertion tambahan**

- ability list tidak mengandung duplicate;
- response session sama dengan token abilities tersimpan;
- device/app name tercatat konsisten;
- token expiry mengikuti kebijakan keamanan.

## 2. Active-Member API Gate

Endpoint active-only harus diuji untuk seluruh status.

| Action | ACTIVE | PENDING | REVISION | REJECTED | INACTIVE | RESIGNED |
|---|---:|---:|---:|---:|---:|---:|
| lihat savings summary | 200 | 403 | 403 | 403 | 403 | 403 |
| request savings withdrawal | 201/422 bisnis | 403 | 403 | 403 | 403 | 403 |
| lihat dues/bills | 200 | 403 | 403 | 403 | 403 | 403 |
| create payment intent | 201/422 bisnis | 403 | 403 | 403 | 403 | 403 |
| upload payment proof | 201 | 403 | 403 | 403 | 403 | 403 |
| apply loan | 201/422 bisnis | 403 | 403 | 403 | 403 | 403 |
| request restructure | 201/422 bisnis | 403 | 403 | 403 | 403 | 403 |
| lihat SHU | 200 | 403 | 403 | 403 | 403 | 403 |
| redeem reward | 201/422 bisnis | 403 | 403 | 403 | 403 | 403 |
| create coffee order | 201 | 403 | 403 | 403 | 403 | 403 |
| create store order | 201 | 403 | 403 | 403 | 403 | 403 |

Endpoint onboarding-safe memiliki matriks terpisah:

| Action | ACTIVE | PENDING | REVISION | REJECTED |
|---|---:|---:|---:|---:|
| onboarding status | 200 | 200 | 200 | 200/read-only |
| update onboarding profile | sesuai policy | 200 | 200 | 403/read-only |
| status journey | 200 | 200 | 200 | 200 |
| notification onboarding | 200 | 200 | 200 | 200 |

Gunakan error code domain yang dapat diandalkan aplikasi Android, bukan hanya message string.

## 3. Ownership Isolation Anggota

| Case | Actor | Resource | Expected |
|---|---|---|---|
| OWN-001 | activeMemberA | invoice A | 200 |
| OWN-002 | activeMemberA | invoice B | 403 atau 404 konsisten |
| OWN-003 | activeMemberA | payment A | 200 |
| OWN-004 | activeMemberA | payment B | 403/404 |
| OWN-005 | activeMemberA | loan A | 200 |
| OWN-006 | activeMemberA | loan B | 403/404 |
| OWN-007 | activeMemberA | payment intent A | 200 |
| OWN-008 | activeMemberA | payment intent B | 403/404 |
| OWN-009 | activeMemberA | POS transaction B | tidak muncul pada list |
| OWN-010 | activeMemberA | reward redemption B | tidak muncul pada list |
| OWN-011 | activeMemberA | support ticket B | tidak muncul/tidak dapat dibaca |
| OWN-012 | activeMemberA | resignation request B | tidak muncul/tidak dapat dibaca |

Untuk mengurangi resource enumeration, pertimbangkan 404 untuk resource milik user lain dan gunakan secara konsisten.

## 4. Export dan PII

| Case | Actor | Expected |
|---|---|---|
| EXPORT-001 | activeMemberA | 403 pada export anggota |
| EXPORT-002 | cashierA tanpa export permission | 403 |
| EXPORT-003 | adminA tanpa export permission | 403 |
| EXPORT-004 | actor dengan export permission Org A | file hanya berisi Org A |
| EXPORT-005 | actor Org A | data Org B tidak muncul |
| EXPORT-006 | export standar | rekening/NPWP dimasking atau tidak disertakan sesuai requirement |
| EXPORT-007 | export full PII khusus | butuh permission khusus dan menghasilkan audit log |
| EXPORT-008 | filter search/status | tidak dapat memperluas scope organisasi |

Test harus membaca isi file hasil export, bukan hanya mengecek HTTP 200.

## 5. Pemisahan Portal Anggota dan Area Admin

| Route/Action | Anggota | Admin Koperasi | Pengurus | Kasir |
|---|---:|---:|---:|---:|
| member dashboard sendiri | allow | sesuai keputusan produk | sesuai keputusan produk | sesuai keputusan produk |
| cooperative members index | deny | allow | allow | read-only bila dibutuhkan |
| cooperative member create/update | deny | allow | allow sesuai matriks | deny |
| member export | deny | permission khusus | permission khusus | deny |
| resignation review inbox | deny | allow/verify | allow/approve | deny |
| loan admin index | deny | allow | allow | read-only sesuai requirement |
| loan review | deny | sesuai matriks | sesuai matriks | deny |
| loan final approval | deny | deny kecuali diberi | allow | deny |
| payment approval | deny | allow | allow sesuai requirement | sesuai requirement |
| opening balance draft | deny | allow | allow | deny |
| opening balance post | deny | deny kecuali diberi | allow | deny |

Direct URL test wajib dilakukan walaupun menu disembunyikan di UI.

## 6. Pengajuan Resign

| Case | Expected |
|---|---|
| Member A melihat status request miliknya | 200 |
| Member A membatalkan request pending miliknya | 200 |
| Member A membatalkan request non-pending | 404/422 |
| Member A membaca request Member B | 403/404 |
| Member A membuka admin resignation index | 403 |
| Admin Koperasi melihat pending requests dalam scope | 200 |
| Admin Org A tidak melihat request Org B | tidak muncul/403 |
| actor tanpa approve permission memproses request | 403 |
| approver memproses request yang sudah selesai | 409/422 |
| approve resign mengubah status dan mencabut token | asserted |
| reject resign tidak mengubah status member menjadi resigned | asserted |
| seluruh transition menghasilkan audit dan notification setelah commit | asserted |

## 7. Lifecycle dan Token Revocation

Untuk setiap transition berikut, buat token sebelum transition lalu gunakan token setelah transition:

| Transition | Expected Token Result |
|---|---|
| ACTIVE → REVISION | token active-member tidak lagi valid |
| PENDING → PENDING_VALIDATION | token onboarding tetap sesuai policy; role aktif tidak diberikan |
| PENDING_VALIDATION → ACTIVE | token lama dapat direvoke dan user login ulang untuk abilities baru |
| PENDING/REVISION → REJECTED | seluruh member token direvoke |
| ACTIVE → INACTIVE | seluruh active-member token direvoke |
| ACTIVE → RESIGNED | seluruh active-member token direvoke |
| member soft deleted | seluruh token direvoke |
| role Anggota dihapus manual | token tidak dapat terus memakai endpoint member |

Test juga harus memastikan revocation tidak terjadi sebelum transaction commit bila transition gagal.

## 8. Organization Isolation

| Case | Actor Org A | Resource Org B | Expected |
|---|---|---|---|
| list member | adminA | members B | tidak muncul |
| show member | adminA | member B | 403/404 |
| update/activate/resign member | adminA | member B | 403 |
| list dues | adminA | invoices B | tidak muncul |
| batch payment | adminA | invoice IDs B | seluruh request ditolak |
| loan list/show/review | adminA/managerA | loan B | tidak muncul/403 |
| reports/stats | adminA | data B | tidak dihitung |
| export | adminA | data B | tidak disertakan |
| POS lookup | cashierA | member/product scope B | tidak tersedia sesuai tenancy rule |

Tambahkan positive test untuk role pusat yang memiliki `view_cooperative_all`.

## 9. Maker-Checker

| Flow | Maker | Checker | Expected |
|---|---|---|---|
| member verification/final approval | adminA | boardA | allow |
| member verification/final approval | adminA | adminA | deny |
| loan review/approval | managerA | boardA | allow |
| loan review/approval | boardA | boardA | deny bila actor separation diwajibkan |
| opening balance draft/post | adminA | boardA | allow |
| opening balance draft/post | boardA | boardA | deny |
| POS void request/process | cashierA | authorized manager | allow |
| POS void request/process | cashierA | cashierA | deny |
| payment record/approve | cashier/admin maker | approver berbeda | allow |

Assertion:

- state tidak berubah pada denial;
- tidak ada ledger side effect;
- audit denial atau exception tercatat sesuai kebijakan;
- emergency override hanya berjalan dengan permission khusus dan reason wajib.

## 10. Permission dan Role Consistency

Test yang disarankan:

- seluruh value `PermissionEnum` ada di database setelah seeder;
- tidak ada permission string pada seeder yang tidak ada di enum, kecuali didokumentasikan;
- permission alias `validate`/`verify` tidak menghasilkan perilaku berbeda;
- role Anggota tidak memiliki permission admin;
- Kasir tidak mempunyai approval/member-management ability yang tidak dibutuhkan;
- Admin Koperasi tidak mempunyai final approval bila maker-checker melarangnya;
- custom role dengan permission valid bekerja tanpa hard-coded role name;
- permission cache di-reset setelah migration/seeding.

## 11. PII Serialization

| Response | Expected |
|---|---|
| member list admin | field minimum; PII sensitif masked/absent |
| member detail authorized | hanya field yang diperlukan |
| member self profile | full own data sesuai requirement |
| unauthorized/cross-org detail | tidak membocorkan data pada error |
| audit log | tidak menyimpan token/password/full QR payload |
| notification | tidak memuat rekening/NPWP penuh |
| exception/log context | tidak memuat upload atau PII penuh |

Gunakan JSON structure assertion agar penambahan field sensitif di masa depan memecahkan test.

## 12. Payment, Idempotency, dan Concurrency

### Payment intent

- request dengan idempotency key sama menghasilkan satu payment/intent;
- request key sama dengan payload berbeda ditolak;
- member tidak dapat memakai intent milik member lain;
- expired intent tidak dapat disettle sebagai order baru tanpa aturan yang jelas;
- webhook duplicate tidak menggandakan ledger/order.

### Store order

- dua request dengan `client_reference` sama menghasilkan satu intent;
- unique constraint tetap melindungi ketika request paralel;
- dua member memesan last stock secara paralel: hanya satu reservation/order berhasil;
- failed/expired payment melepaskan reservation;
- successful settlement mengurangi stock satu kali;
- retry settlement idempotent.

## 13. Pagination dan Abuse Resistance

Untuk endpoint list:

- `per_page=0` dikoreksi atau ditolak;
- `per_page=-1` dikoreksi atau ditolak;
- `per_page=100000` dibatasi ke maksimum;
- search panjang dibatasi;
- invalid date/status/category menghasilkan 422;
- nested relations tidak menyebabkan jumlah query berlebihan pada fixture besar.

## Suggested Test Files

Nama dapat disesuaikan dengan struktur test proyek:

```text
tests/Feature/Auth/MemberTokenAbilityTest.php
tests/Feature/Auth/AdminTokenAbilityTest.php
tests/Feature/Member/MemberActiveStatusGateTest.php
tests/Feature/Member/MemberOwnershipIsolationTest.php
tests/Feature/Member/MemberLifecycleTokenRevocationTest.php
tests/Feature/Cooperative/MemberExportAuthorizationTest.php
tests/Feature/Cooperative/MemberResignationAuthorizationTest.php
tests/Feature/Cooperative/LoanAdministrationAuthorizationTest.php
tests/Feature/Cooperative/OrganizationIsolationTest.php
tests/Feature/Cooperative/MakerCheckerTest.php
tests/Feature/Cooperative/PermissionMatrixTest.php
tests/Feature/Cooperative/PiiSerializationTest.php
tests/Feature/Store/MemberStoreConcurrencyTest.php
```

## CI Gate yang Disarankan

Minimal sebelum merge perubahan authorization:

```bash
php artisan config:clear
php artisan permission:cache-reset
php artisan test --testsuite=Feature
```

Tambahkan job fokus security agar feedback lebih cepat:

```bash
php artisan test \
  tests/Feature/Auth \
  tests/Feature/Member \
  tests/Feature/Cooperative/MemberExportAuthorizationTest.php \
  tests/Feature/Cooperative/OrganizationIsolationTest.php
```

## Exit Criteria

Authorization hardening dianggap memenuhi baseline ketika:

- semua case P0 memiliki regression test;
- tidak ada role Anggota yang dapat membuka route admin;
- seluruh cross-owner dan cross-organization test lulus;
- status nonaktif selalu ditolak pada endpoint finansial;
- token wildcard tidak diterbitkan untuk aplikasi mobile;
- lifecycle transition mencabut akses;
- maker-checker memiliki negative test;
- export dan response tidak membocorkan PII;
- concurrency/idempotency critical path memiliki database-backed test.