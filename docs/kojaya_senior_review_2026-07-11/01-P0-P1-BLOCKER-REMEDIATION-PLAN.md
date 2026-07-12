# P0/P1 Blocker Remediation Plan

Dokumen ini menjadi instruksi prioritas untuk Codex sebelum melanjutkan P2 final.

## Urutan pengerjaan wajib

### Wave 0 — Stabilkan branch dan CI

#### Task 0.1 — Perbaiki Wayfinder drift

Jalankan route generation dan commit seluruh generated artifacts yang berubah.

Acceptance criteria:

- `php artisan wayfinder:generate` atau command proyek yang ekuivalen tidak menghasilkan diff baru;
- GitHub Actions melewati `Check Wayfinder drift`;
- PHPUnit dan check berikutnya benar-benar berjalan.

#### Task 0.2 — Ubah identitas PR

PR tidak boleh lagi disebut docs-only.

Judul yang disarankan:

```text
draft: member and cooperative authorization hardening
```

Body wajib menjelaskan:

- P0/P1 work-in-progress;
- P2 partial;
- migration yang ditambahkan;
- known blockers;
- test yang belum berjalan;
- rencana pemecahan PR.

### Wave 1 — Satukan state machine anggota

#### Task 1.1 — Buat `MemberStatusTransitionService`

Semua perubahan lifecycle harus melewati satu service:

- `verifyByAdmin`
- `approveFinal`
- `requestRevision`
- `reject`
- `activate`
- `deactivate`
- `resign`
- `delete`

Service wajib mengatur secara atomik:

- `status`;
- `validation_status`;
- tanggal/status terkait;
- role `Anggota`;
- token revocation;
- audit;
- notification setelah commit.

#### Matriks state minimum

| Transition | status | validation_status | role Anggota | member API token |
|---|---|---|---|---|
| pending | PENDING/INACTIVE | PENDING | optional onboarding role | onboarding token only |
| verified admin | PENDING/INACTIVE | PENDING_VALIDATION | tidak memberi financial access | revoke/rotate member financial token |
| revision | INACTIVE | REVISION | tidak aktif | revoke member app tokens |
| rejected | INACTIVE | REJECTED | tidak aktif | revoke member app tokens |
| final approve | ACTIVE | ACTIVE | assigned | new login/rotation required |
| deactivate | INACTIVE | INACTIVE | remove/disable access | revoke member app tokens |
| resign | RESIGNED | RESIGNED | remove/disable access | revoke member app tokens |
| delete | deleted | deleted-equivalent | remove | revoke member app tokens |

#### Task 1.2 — Perbaiki middleware web

Jangan lagi menggunakan fallback:

```php
$member->validation_status ?: $member->status
```

Untuk fitur finansial, syarat harus eksplisit:

```php
$member->status === ACTIVE
&& $member->validation_status === ACTIVE
```

Untuk onboarding-safe routes, gunakan policy/state method khusus.

#### Task 1.3 — Tambahkan web-session lifecycle tests

Test wajib:

1. User login web sebagai member aktif.
2. Admin menonaktifkan/member resign.
3. Session user yang sama mencoba:
   - savings;
   - loans;
   - points;
   - rewards;
   - transactions.
4. Semua harus ditolak/redirect onboarding/inactive page.

Tidak harus menghapus seluruh session database secara paksa bila middleware selalu mengecek status terbaru, tetapi akses sensitif tidak boleh lolos.

### Wave 2 — Tutup seluruh active-gate bypass

#### Endpoint yang wajib diperbaiki

- `POST /api/payments/charge`
- `GET /api/v1/points/balance`
- `GET /api/v1/points/history`
- `GET /api/v1/rewards`
- `POST /api/v1/rewards/{reward}/redeem`

Rekomendasi:

- pindahkan points/rewards ke grup `member.api.active`; atau
- pasang `member.api.active` langsung pada route;
- `payments/charge` harus mendapat middleware yang sama dan ownership check.

Pastikan juga endpoint baru di masa depan tidak dapat terdaftar di luar gate tanpa test arsitektur.

#### Architecture test

Tambahkan test yang menginspeksi route collection:

- semua route ber-ability `member:write` yang bersifat finansial/transaksional harus memiliki `member.api.active`;
- whitelist hanya onboarding/profile/notification routes.

### Wave 3 — Organization isolation dan direct-object authorization

#### Task 3.1 — Policy harus memeriksa organisasi

Untuk role non-global:

```php
sameOrganization($user, $model)
```

harus menjadi syarat pada:

- CooperativeMember view/update/activate/deactivate/resign/delete;
- MemberResignationRequest view/approve;
- Loan view/review/approve/reject/disburse/payment;
- payment, ledger, withdrawal, POS credit, dan object koperasi sensitif lainnya.

Permission `manage_*` tidak boleh otomatis berarti global.

Hanya `view_cooperative_all` atau permission global yang secara eksplisit boleh melewati scope organisasi.

#### Task 3.2 — API list dan batch harus memakai centralized scope

`CooperativeMemberApiController::canViewAllMembers()` harus dihapus atau diubah agar `manage_cooperative_member` tidak berarti melihat semua organisasi.

Semua query list/stat/export/batch harus memakai satu service/scope.

#### Task 3.3 — Scope supporting props

Scope daftar:

- employees;
- users;
- active members;
- loan stats;
- loan type bila organization-specific;
- reviewer/approval lists.

#### Negative tests wajib

Untuk Admin Koperasi organisasi A:

- GET detail member B → 403/404;
- PUT member B → 403/404;
- activate/deactivate/resign member B → 403/404;
- process resignation B → 403/404;
- GET loan B → 403/404;
- review/approve/reject/disburse/pay loan B → 403/404;
- API list tidak mengandung B;
- API direct object tidak mengembalikan B;
- batch dengan campuran A+B harus reject seluruh batch, bukan silently skip.

### Wave 4 — Amankan member-user provisioning

#### Risiko

Pencarian user berdasarkan email dapat menautkan member ke user yang sudah ada dan memberi role `Anggota`.

#### Invariant yang wajib

- user target tidak boleh sudah menjadi member lain;
- privileged role harus ditolak atau memerlukan explicit elevated flow;
- organisasi harus cocok, kecuali dilakukan System Admin melalui flow khusus;
- email match saja tidak cukup sebagai bukti identitas untuk account linking;
- perubahan `user_id` harus diaudit;
- link existing user harus memakai explicit selected `user_id` + authorization, atau invitation/verification token.

#### Acceptance tests

- Admin Koperasi tidak dapat menautkan member ke System Admin.
- Admin org A tidak dapat menautkan user org B.
- Email yang kebetulan sama tetapi tidak diverifikasi tidak langsung ditautkan.
- Existing ordinary user yang valid dapat ditautkan melalui flow resmi.
- Rollback terjadi bila provisioning gagal.

### Wave 5 — Persempit token revocation

#### Perubahan

Jangan selalu memakai:

```php
$user->tokens()->delete();
```

Targetkan token aplikasi anggota berdasarkan salah satu kontrak:

- token name prefix resmi;
- metadata/app column;
- abilities `member:*`;
- dedicated token registry.

#### Acceptance tests

User memiliki:

- token member;
- token ESS;
- token technician.

Setelah resign/deactivate:

- token member hilang;
- token ESS/technician tetap ada bila account employment masih aktif;
- opsi account-wide revoke hanya dilakukan untuk security incident dan tercatat reason khusus.

### Wave 6 — Aktivasi dan token issuance

`TokenAbilityResolver` dapat tetap memberi onboarding abilities kepada profile pending, tetapi:

- write financial access harus ditentukan route gate;
- token member aktif hanya efektif ketika current state ACTIVE;
- setelah approval final, user login/rotate token untuk abilities terbaru;
- jangan mengandalkan role cache lama.

Tambahkan test untuk:

- pending login;
- revision login;
- rejected login;
- active login;
- resigned login;
- app=member untuk System Admin tidak menghasilkan admin abilities.
