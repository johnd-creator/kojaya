# Inventaris Workflow Aktif per Peran

Daftar workflow yang diimplementasikan aplikasi dan tercakup dalam
pusat panduan. Setiap workflow diverifikasi terhadap source code
commit `20c86960`.

## Anggota

- `anggota-portal-overview` — `route('member.dashboard')`, `member.profile`, `member.savings`, `member.loans`, `member.points`, `member.rewards`, `member.transactions`, `member.store-account`, `member.notifications`, `member.onboarding`
- `anggota-payment-flow` — `member.payments.intent`, `member.payments.proof`, `member.payments.status`, `cooperative.payments.index`, `cooperative.payments.approve`, `cooperative.payments.bulk-approve`
- `anggota-loan-flow` — `member.loans`, `member.loans.store`, `member.loans.installments.payment-intent`, `member.loans.payment-intents.status`

## Admin Koperasi

- `admin-koperasi-operational-dashboard` — `cooperative.operator.dashboard`, `cooperative.members.*`, `cooperative.members.resignations.index`, `cooperative.members.opening-balance.show`, `cooperative.dues.index`, `cooperative.loans.*`, `cooperative.loan-types.*`, `cooperative.pos.*`
- `admin-koperasi-loan-types` — `cooperative.loan-types.index`, `cooperative.loan-types.store`, `cooperative.loan-types.update`, `cooperative.loan-types.destroy`
- `admin-koperasi-pos-inventory` — `cooperative.pos.*`, `cooperative.pos.shifts.*`, `cooperative.pos.transactions.*`, `cooperative.pos.coffee-orders.*`, `cooperative.pos.void-requests.*`, `cooperative.pos.returns.*`, `cooperative.pos.credit.*`, `cooperative.pos.closings.*`, `cooperative.pos.inventory.*`, `cooperative.pos.reports.index`, `cooperative.reports.index`, `cooperative.shu.index`
- `admin-koperasi-payment-queue` — `cooperative.payments.index`, `cooperative.payments.approve`, `cooperative.payments.bulk-approve`

## Manajer Koperasi

- `manajer-loan-review` — `cooperative.loans.index`, `cooperative.loans.show`, `cooperative.loans.review`, `cooperative.loans.approve`, `cooperative.loans.reject`, `cooperative.ledger.index`
- `manajer-financial-monitoring` — `cooperative.operator.dashboard`, `cooperative.payments.index`, `cooperative.pos.closings.index`, `cooperative.ledger.index`, `cooperative.loans.index`, `cooperative.loans.disburse`, `cooperative.reports.index`

## Pengurus Koperasi

- `pengurus-loan-approval` — `cooperative.loans.index`, `cooperative.loans.show`, `cooperative.loans.approve`, `cooperative.loans.disburse`, `cooperative.loans.reject`, `audit-logs`
- `pengurus-shu-and-governance` — `cooperative.shu.index`, `cooperative.shu.close`, `cooperative.shu.request-revision`, `cooperative.points.index`, `cooperative.reports.index`, `audit-logs`, `exceptions.index`

## Semua Peran

- `shared-glossary` — glosarium istilah koperasi

## Belum Tercakup / Gap

| Workflow | Catatan |
| --- | --- |
| Pembatalan aplikasi pinjaman oleh anggota | Belum diimplementasikan |
| Quorum atau persetujuan multi-Pengurus | Belum diimplementasikan |
| RAT minute (model `RatMinute`) | Belum diimplementasikan |
| Approval minute (model `ApprovalMinute`) | Belum diimplementasikan |
| SLA review pinjaman otomatis | Belum diimplementasikan |
| Preset laporan RAT/AnnualReport/Pengurus | Belum diimplementasikan |
