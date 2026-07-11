# P0 Security and Correctness Closure Plan

## Scope lock

```text
Target: johnd-creator/kojaya
Forbidden: johnd-creator/KojayaApp
```

Jangan mengubah UI Android, fitur baru, copywriting, atau payment state machine pada wave ini.

## Target

Menutup:

- CI yang belum mengeksekusi test;
- export lintas organisasi;
- lifecycle mutation bypass;
- unauthorized PII write dan accidental PII deletion;
- account linking melalui generic update;
- data legacy yang tidak kompatibel dengan strict active gate.

---

## P0-A — Pulihkan CI terlebih dahulu

### Instruksi

1. Checkout exact branch PR.
2. Jalankan:
   ```bash
   composer install
   npm ci
   cp .env.example .env
   php artisan key:generate
   php artisan wayfinder:generate
   git status --short
   ```
3. Review generated diff dan commit generated artifacts yang benar.
4. Jalankan generator kedua kali.
5. Generator kedua kali wajib zero diff.
6. Jangan men-disable drift check, menambahkan `|| true`, atau mengecualikan generated files.

### Acceptance

```bash
vendor/bin/pint --test
npm run build
php artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
php artisan test --compact --parallel
bin/openapi.sh check
php artisan migrate:fresh --seed
```

Semua exit code `0`.

---

## P0-B — Export scope fail-closed

### Masalah

Nullable organization ID dipakai untuk dua arti:

- global visibility;
- non-global user tanpa organization.

### Desain wajib

Gunakan explicit value object:

```php
final readonly class OrganizationVisibility
{
    private function __construct(
        public bool $global,
        public ?string $organizationId,
    ) {}

    public static function global(): self;
    public static function organization(string $id): self;
}
```

Lebih baik lagi, export menerima query yang sudah scoped.

### Rules

- `view_cooperative_all` -> global.
- non-global + organization -> exact organization.
- non-global + null organization -> 403.
- tidak ada silent unscoped query.
- unsupported scope -> exception.

### Tests

- Pengurus global export A+B.
- Admin A export hanya A.
- non-global null-org mendapat 403.
- export file aktual tidak memuat sentinel org B.
- audit menyimpan explicit scope.

---

## P0-C — Pisahkan empat command

Generic member update harus dipecah:

```text
UpdateMemberProfile
UpdateMemberSensitiveData
LinkMemberAccount
TransitionMemberStatus
```

### Profile update boleh

- name;
- email sesuai policy;
- phone;
- metadata anggota non-sensitive.

### Dedicated PII update

Tambahkan permission terpisah:

```text
update_cooperative_member_pii
```

Field:

- identity_number;
- NPWP;
- bank account;
- bank/account holder;
- sensitive address/notes sesuai klasifikasi.

`view_cooperative_member_pii` tidak otomatis berarti write.

### Dedicated account linking

Generic update dilarang menerima:

- `user_id`;
- implicit email link;
- organization reassignment.

Gunakan endpoint/action khusus dengan reason dan audit.

### Lifecycle fields dilarang di generic update

- `status`;
- `validation_status`;
- validation timestamps/actors;
- `resigned_at`;
- role fields.

### Fix accidental bank-account deletion

Setelah PII fields dihapus karena permission, jangan menulis:

```php
'no_rekening' => $data['no_rekening'] ?? null
```

Gunakan partial update: field absent berarti preserve.

### API parity

Aturan web dan API harus sama. Jangan hanya memperbaiki satu controller.

### Tests

1. Admin tanpa PII-write mengubah nama; seluruh PII tetap.
2. Unauthorized NPWP write ditolak.
3. PII viewer tanpa write tidak dapat update.
4. Authorized same-org PII update berhasil.
5. Cross-org PII update ditolak.
6. Generic update `status=INACTIVE` ditolak.
7. Generic update `user_id` ditolak.
8. Omitted PII preserved.
9. Explicit clear PII hanya lewat authorized action.
10. Web/API behavior identik.

---

## P0-D — Domain-enforced lifecycle state machine

Jadikan generic `transition()` private.

Public methods hanya:

```text
verifyByAdmin
requestRevision
approveFinal
reject
activate
deactivate
resign
deleteAccess
```

### Allowed transitions

| Source | Command | Target |
|---|---|---|
| PENDING/PENDING | verifyByAdmin | PENDING/PENDING_VALIDATION |
| INACTIVE/REVISION | verifyByAdmin | PENDING/PENDING_VALIDATION |
| PENDING/PENDING_VALIDATION | approveFinal | ACTIVE/ACTIVE |
| PENDING/PENDING_VALIDATION | requestRevision | INACTIVE/REVISION |
| PENDING/PENDING_VALIDATION | reject | INACTIVE/REJECTED |
| INACTIVE/INACTIVE | activate | ACTIVE/ACTIVE |
| ACTIVE/ACTIVE | deactivate | INACTIVE/INACTIVE |
| ACTIVE/ACTIVE | resign | RESIGNED/RESIGNED |

Reactivate REJECTED/RESIGNED membutuhkan keputusan eksplisit; jangan otomatis.

### Domain algorithm

Di dalam transaction setelah row lock:

1. re-read current state;
2. validate exact source state;
3. validate actor authorization bila service dapat dipanggil di luar controller;
4. write both status fields;
5. role update;
6. audit;
7. token revocation after commit;
8. notification after commit.

### Tests

- valid transition matrix;
- setiap invalid transition;
- direct service call tidak bypass guard;
- concurrent approve versus reject hanya satu menang;
- generic profile update tidak mengubah status;
- double-submit contract jelas: idempotent atau 409.

---

## P0-E — Legacy status preflight

Buat command read-only:

```bash
php artisan members:audit-status-consistency
```

Output:

- total;
- ACTIVE/ACTIVE;
- ACTIVE/null;
- ACTIVE/non-active-validation;
- non-active/ACTIVE;
- unknown values;
- sample IDs tanpa PII.

### Backfill policy

- ACTIVE + null legacy row -> candidate ACTIVE setelah verified rule.
- INACTIVE/RESIGNED + validation ACTIVE -> terminal validation.
- REVISION/REJECTED preserved.
- unknown -> manual review.

### Deployment

1. audit staging copy;
2. review report;
3. backup;
4. idempotent backfill;
5. audit ulang;
6. deploy strict gate;
7. monitor denied access.

### Done

- zero known contradictory states;
- unknown rows listed;
- migrated legacy test passes;
- no PII in output.
