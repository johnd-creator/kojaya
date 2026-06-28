# Rencana Notifikasi Koperasi

## Status Implementasi

- 2026-06-28: Phase 0/P0 dieksekusi untuk kontrak API session, member token, dan admin token: `recent`, `summary`, filtering, mark read, mark all read, ownership guard, dan preference kategori.
- 2026-06-28: Ikon kanan atas memakai endpoint `recent` + `summary`, polling 30 detik, link role-aware (`/member/notifications` untuk Anggota, `/notifications` untuk internal), dan action click mark-read lalu navigasi.
- 2026-06-28: P1 awal dieksekusi untuk transaksi prioritas pinjaman dan pembayaran simpanan melalui `CooperativeNotificationDispatcher` dengan deduplication key.

## Status Awal

Notifikasi belum menjadi workflow terpadu. Fondasi teknis sudah ada melalui tabel Laravel `notifications`, `notification_outboxes`, service database/push/WhatsApp, route session `/api/notifications/*`, dan route member `/api/v1/member/notifications`. Namun ikon notifikasi kanan atas aplikasi belum dapat dianggap berfungsi penuh karena:

- Payload notifikasi belum distandardisasi lintas modul.
- Belum semua transaksi koperasi mengirim event notifikasi.
- Dropdown kanan atas masih bergantung pada kontrak frontend lama dan belum role-aware.
- Kontrak API belum membedakan konteks admin koperasi dan anggota secara lengkap.
- Belum ada preference per kategori transaksi.

Dokumen ini menjadi rencana phase-by-phase untuk role:

- `Anggota`
- `Admin Koperasi`
- `Manajer Koperasi`
- `Pengurus Koperasi`

## Tujuan

1. Membuat notifikasi database yang konsisten untuk seluruh transaksi koperasi.
2. Menghidupkan ikon notifikasi kanan atas untuk semua role internal dan anggota.
3. Menyediakan API notifikasi yang stabil untuk web Inertia dan mobile.
4. Mengaktifkan notifikasi push/WhatsApp secara bertahap setelah database notification stabil.
5. Menjaga auditability: setiap notifikasi memiliki event type, subject, action URL, role target, dan status baca.

## Prinsip Desain

- Database notification adalah source of truth untuk in-app notification.
- Push dan WhatsApp hanya channel delivery tambahan, bukan sumber data utama.
- Event notifikasi harus dikirim setelah transaksi commit, idealnya melalui `NotificationOutboxService`.
- Semua notifikasi harus memiliki `event_type`, `category`, `severity`, `subject_type`, `subject_id`, `title`, `message`, `action_url`, dan `metadata`.
- Notifikasi approval harus dikirim ke role yang benar, bukan hanya ke user hardcoded.
- Notifikasi anggota harus hanya berisi data milik anggota tersebut.
- Notifikasi admin/manajer/pengurus harus mengikuti organisasi dan permission.

## Target Payload Standar

```json
{
  "id": "uuid",
  "type": "database",
  "event_type": "cooperative.loan.manager_review_required",
  "category": "loan",
  "severity": "info",
  "title": "Review pinjaman menunggu Manajer Koperasi",
  "message": "Pengajuan pinjaman Andi Prasetyo sebesar Rp1.200.000 perlu direview.",
  "subject": {
    "type": "loan",
    "id": 123,
    "label": "Pinjaman Andi Prasetyo"
  },
  "actor": {
    "id": 45,
    "name": "Admin Koperasi"
  },
  "action": {
    "label": "Buka detail",
    "url": "/cooperative/loans/123"
  },
  "read_at": null,
  "created_at": "2026-06-28T10:00:00+07:00",
  "metadata": {
    "organization_id": "uuid",
    "member_id": 10,
    "amount": 1200000
  }
}
```

## Role dan Kanal

| Role | In-App | Push | WhatsApp | Catatan |
| --- | --- | --- | --- | --- |
| Anggota | Wajib | Phase 4 | Phase 4 | Untuk status pengajuan, pembayaran, jatuh tempo, poin, reward, POS, SHU |
| Admin Koperasi | Wajib | Opsional | Tidak default | Untuk antrean operasional dan transaksi yang perlu diproses |
| Manajer Koperasi | Wajib | Opsional | Tidak default | Untuk review pinjaman dan eskalasi operasional |
| Pengurus Koperasi | Wajib | Opsional | Opsional | Untuk final approval, laporan kritis, closing, SHU, dan exception |

## Event Matrix

### Anggota

| Domain | Trigger | Event Type | Prioritas | Action |
| --- | --- | --- | --- | --- |
| Onboarding | Data dikirim | `member.onboarding.submitted` | Info | `/member/onboarding` |
| Onboarding | Perlu revisi | `member.onboarding.revision_requested` | Warning | `/member/onboarding` |
| Onboarding | Disetujui | `member.onboarding.approved` | Success | `/member` |
| Simpanan | Tagihan simpanan pokok/wajib dibuat | `member.dues.invoice_created` | Info | `/member/savings` |
| Simpanan | Pembayaran bukti manual diterima sistem | `member.payment.proof_uploaded` | Info | `/member/savings` |
| Simpanan | Pembayaran disetujui | `member.payment.approved` | Success | `/member/savings` |
| Simpanan | Pembayaran ditolak/perlu revisi | `member.payment.rejected` | Warning | `/member/savings` |
| Simpanan | Jatuh tempo H-3/H/H+7 | `member.dues.due_reminder` | Warning | `/member/savings` |
| Pinjaman | Pengajuan berhasil dikirim | `member.loan.applied` | Info | `/member/loans` |
| Pinjaman | Direview Manajer Koperasi | `member.loan.manager_reviewed` | Info | `/member/loans` |
| Pinjaman | Final approval Pengurus | `member.loan.approved` | Success | `/member/loans` |
| Pinjaman | Ditolak | `member.loan.rejected` | Warning | `/member/loans` |
| Pinjaman | Dicairkan | `member.loan.disbursed` | Success | `/member/loans` |
| Pinjaman | Angsuran jatuh tempo H-3/H/H+7 | `member.loan.installment_due` | Warning | `/member/loans` |
| Pinjaman | Angsuran diterima | `member.loan.payment_recorded` | Success | `/member/loans` |
| Restrukturisasi | Permintaan dikirim | `member.loan_restructure.submitted` | Info | `/member/loans` |
| Restrukturisasi | Disetujui/ditolak | `member.loan_restructure.status_changed` | Warning/Success | `/member/loans` |
| POS | Transaksi selesai | `member.pos.transaction_completed` | Info | `/member/transactions` |
| POS | Retur/refund diproses | `member.pos.return_processed` | Info | `/member/transactions` |
| Kredit POS | Kredit jatuh tempo | `member.pos_credit.due_reminder` | Warning | `/member/transactions` |
| Kopi | Pesanan diterima | `member.coffee_order.received` | Info | `/member/transactions` |
| Kopi | Status berubah: brewing/ready/picked up/cancelled | `member.coffee_order.status_changed` | Info | `/member/transactions` |
| Poin | Poin bertambah | `member.points.earned` | Info | `/member/points` |
| Poin | Poin dikoreksi/kedaluwarsa | `member.points.adjusted` | Warning | `/member/points` |
| Reward | Redeem dikirim | `member.reward.redeemed` | Info | `/member/rewards` |
| Reward | Status redeem berubah | `member.reward.status_changed` | Info | `/member/reward-redemptions` |
| SHU | SHU tahunan ditutup | `member.shu.allocated` | Success | `/member/shu` |
| Support | Tiket dibuat/dibalas/ditutup | `member.support.status_changed` | Info | `/member/support` |

### Admin Koperasi

| Domain | Trigger | Event Type | Prioritas | Action |
| --- | --- | --- | --- | --- |
| Anggota | Onboarding baru masuk | `admin.member.validation_required` | Info | `/cooperative/members` |
| Anggota | Revisi anggota dikirim ulang | `admin.member.revision_resubmitted` | Info | `/cooperative/members` |
| Simpanan | Bukti pembayaran manual masuk | `admin.payment.approval_required` | Warning | `/cooperative/payments?status=PENDING` |
| Simpanan | Bulk approve selesai/gagal sebagian | `admin.payment.bulk_result` | Info | `/cooperative/payments` |
| Iuran | Generate iuran selesai/gagal | `admin.dues.generation_result` | Info/Warning | `/cooperative/dues` |
| Pinjaman | Pengajuan baru dibuat admin/anggota | `admin.loan.created` | Info | `/cooperative/loans` |
| Pinjaman | Final approval selesai dan siap pencairan | `admin.loan.ready_for_disbursement` | Warning | `/cooperative/loans` |
| Pinjaman | Pinjaman dicairkan | `admin.loan.disbursed` | Info | `/cooperative/loans` |
| Angsuran | Pembayaran angsuran dicatat | `admin.loan.payment_recorded` | Info | `/cooperative/loans` |
| POS | Shift dibuka/ditutup | `admin.pos.shift_status_changed` | Info | `/cooperative/pos/shifts` |
| POS | Void request dibuat | `admin.pos.void_requested` | Warning | `/cooperative/pos/void-requests` |
| POS | Retur dibuat | `admin.pos.return_created` | Info | `/cooperative/pos/returns` |
| Inventory | Stok minimum/negatif | `admin.inventory.low_stock` | Warning | `/cooperative/pos-products` |
| Inventory | Stock opname butuh review | `admin.inventory.count_review_required` | Warning | `/cooperative/pos/inventory/counts` |
| Kopi | Pesanan kopi baru | `admin.coffee_order.received` | Info | `/cooperative/pos/coffee-orders` |
| Reward | Redeem reward baru | `admin.reward.redemption_required` | Warning | `/cooperative/redemptions` |
| Support | Tiket anggota baru | `admin.support.ticket_created` | Info | `/cooperative/support-tickets` |

### Manajer Koperasi

| Domain | Trigger | Event Type | Prioritas | Action |
| --- | --- | --- | --- | --- |
| Pinjaman | Pengajuan `APPLIED` baru | `manager.loan.review_required` | Warning | `/cooperative/loans?status=APPLIED` |
| Pinjaman | Review manager berhasil | `manager.loan.review_completed` | Info | `/cooperative/loans` |
| Pinjaman | Ditolak pada tahap manager | `manager.loan.rejected` | Info | `/cooperative/loans` |
| Pinjaman | Final approval Pengurus selesai | `manager.loan.final_approved` | Info | `/cooperative/loans` |
| Pinjaman | Pencairan belum dilakukan lebih dari SLA | `manager.loan.disbursement_overdue` | Warning | `/cooperative/loans?status=APPROVED` |
| Pembayaran | Anomali pembayaran besar/duplikat | `manager.payment.exception_detected` | Warning | `/cooperative/payments` |
| POS | Void/return bernilai tinggi | `manager.pos.exception_detected` | Warning | `/cooperative/pos/void-requests` |
| Inventory | Selisih stock opname besar | `manager.inventory.variance_detected` | Warning | `/cooperative/pos/inventory/counts` |
| Closing | Closing harian terlambat | `manager.closing.overdue` | Warning | `/cooperative/operator/closing` |
| SHU | Periode SHU siap direview | `manager.shu.review_required` | Info | `/cooperative/shu` |

### Pengurus Koperasi

| Domain | Trigger | Event Type | Prioritas | Action |
| --- | --- | --- | --- | --- |
| Anggota | Anggota lolos validasi admin dan butuh final approval | `pengurus.member.final_approval_required` | Warning | `/cooperative/members` |
| Pinjaman | Manager review selesai | `pengurus.loan.final_approval_required` | Warning | `/cooperative/loans?status=MANAGER_APPROVED` |
| Pinjaman | Final approval dilakukan | `pengurus.loan.final_approved` | Info | `/cooperative/loans` |
| Pinjaman | NPL/default threshold tercapai | `pengurus.loan.npl_alert` | Critical | `/cooperative/reports` |
| Simpanan | Koreksi/void ledger butuh approval | `pengurus.ledger.correction_required` | Warning | `/cooperative/ledger` |
| Opening Balance | Batch saldo awal siap posting/void | `pengurus.opening_balance.approval_required` | Warning | `/cooperative/opening-balances` |
| POS | Void bernilai tinggi/banyak | `pengurus.pos.void_exception` | Warning | `/cooperative/pos/void-requests` |
| Closing | Closing harian selesai dengan selisih kas | `pengurus.closing.cash_difference` | Warning | `/cooperative/operator/closing` |
| SHU | Periode SHU siap ditutup | `pengurus.shu.close_required` | Warning | `/cooperative/shu` |
| SHU | Revisi SHU diminta | `pengurus.shu.revision_requested` | Critical | `/cooperative/shu` |
| Laporan | Export laporan selesai/gagal | `pengurus.report.export_result` | Info/Warning | `/cooperative/reports` |
| Sistem | Outbox notifikasi gagal berulang | `pengurus.notification.outbox_failed` | Critical | `/monitoring/health` |

## Kontrak API Target

### Session/Web API

Base path: `/api/notifications`

Digunakan oleh ikon kanan atas dan halaman notifikasi web untuk semua role yang login via session.

#### List Notifications

```http
GET /api/notifications?category=loan&status=unread&severity=warning&per_page=10
```

Response:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 0,
    "unread_count": 0
  }
}
```

#### Notification Summary

```http
GET /api/notifications/summary
```

Response:

```json
{
  "unread_count": 5,
  "by_category": {
    "loan": 2,
    "payment": 1,
    "pos": 2
  },
  "by_severity": {
    "info": 2,
    "warning": 3,
    "critical": 0
  }
}
```

#### Recent Dropdown Feed

```http
GET /api/notifications/recent?limit=5
```

Digunakan khusus ikon kanan atas. Harus cepat, role-aware, dan hanya mengembalikan payload minimal.

#### Mark Read

```http
PATCH /api/notifications/{notification}/read
POST /api/notifications/mark-all-read
```

#### Preferences

```http
GET /api/notifications/preferences
PUT /api/notifications/preferences
```

Request:

```json
{
  "database_enabled": true,
  "push_enabled": true,
  "whatsapp_enabled": false,
  "whatsapp_phone": "6281234567890",
  "categories": {
    "loan": ["database", "push"],
    "payment": ["database"],
    "dues": ["database", "whatsapp"],
    "pos": ["database"],
    "shu": ["database", "push"]
  }
}
```

### Mobile Member API

Base path: `/api/v1/member/notifications`

Tetap dipakai Kojayaku. Kontrak harus disejajarkan dengan session/web API.

```http
GET /api/v1/member/notifications
GET /api/v1/member/notifications/unread-count
GET /api/v1/member/notifications/summary
PATCH /api/v1/member/notifications/{notification}/read
POST /api/v1/member/notifications/mark-all-read
GET /api/v1/member/notifications/preferences
PUT /api/v1/member/notifications/preferences
```

### Admin Mobile/API

Base path: `/api/v1/notifications`

Untuk role internal yang memakai token Sanctum `admin` atau aplikasi operasional.

```http
GET /api/v1/notifications
GET /api/v1/notifications/recent
GET /api/v1/notifications/summary
PATCH /api/v1/notifications/{notification}/read
POST /api/v1/notifications/mark-all-read
GET /api/v1/notifications/preferences
PUT /api/v1/notifications/preferences
```

Authorization:

- `cooperative:read` untuk list/summary/recent.
- `cooperative:write` untuk mark read dan update preferences.
- Server tetap memfilter berdasarkan authenticated user, role, permission, dan organization.

## Phase 0 - Audit dan Normalisasi Dasar

Target: fondasi tidak ambigu sebelum menambah event baru.

Tasks:

1. Audit route existing:
   - `/api/notifications/*`
   - `/api/v1/member/notifications`
   - `/api/ess/notifications`
2. Standardisasi `NotificationResource`.
3. Tambahkan field payload standar pada semua database notifications baru.
4. Pastikan ikon kanan atas memakai endpoint `recent` dan `summary`, bukan list paginated penuh.
5. Pastikan link "Lihat semua notifikasi" role-aware:
   - Anggota: `/member/notifications`
   - Internal: `/notifications`
6. Tambahkan test untuk unread count, mark read, mark all read, dan filter user ownership.

Acceptance:

- Ikon kanan atas menampilkan unread count yang benar.
- Dropdown kanan atas menampilkan maksimal 5 notifikasi terbaru.
- Mark read dari dropdown mengurangi unread count tanpa reload penuh.
- User tidak dapat membaca notifikasi milik user lain.

## Phase 1 - In-App Notifications untuk Transaksi Inti

Target: semua transaksi koperasi inti menghasilkan database notification.

Scope:

- Onboarding anggota.
- Pembayaran simpanan dan iuran.
- Pinjaman: apply, manager review, final approval, reject, disburse, installment payment.
- POS: transaksi, retur, void request/result, coffee order.
- Reward redemption.
- Support ticket.

Tasks:

1. Buat `CooperativeNotificationDispatcher`.
2. Buat mapping recipient by role/permission:
   - Admin Koperasi: operational queue.
   - Manajer Koperasi: review/exception queue.
   - Pengurus Koperasi: final approval/high-risk queue.
   - Anggota: owned transaction status.
3. Panggil dispatcher dari service domain setelah transaksi commit.
4. Tambahkan event type constants atau enum.
5. Tambahkan feature tests untuk minimal satu happy path per domain.

Acceptance:

- Setiap transaksi inti membuat notifikasi ke penerima yang benar.
- Tidak ada duplicate notification untuk retry/idempotency key yang sama.
- Notifikasi memiliki action URL valid.

## Phase 2 - Approval dan Exception Notifications

Target: role internal tidak perlu mencari antrean approval secara manual.

Scope approval:

- Validasi anggota final oleh Pengurus.
- Pinjaman review Manajer dan final approval Pengurus.
- Payment pending approval.
- Opening balance posting/void approval.
- POS void/return high-risk.
- Stock opname review.
- SHU close/revision.

Scope exception:

- Pinjaman approved tapi belum cair melewati SLA.
- Angsuran overdue/NPL threshold.
- Simpanan wajib overdue.
- Selisih kas closing.
- Stok negatif/minimum.
- Outbox notification failed.

Tasks:

1. Tambahkan scheduled command untuk scan exception harian.
2. Tambahkan deduplication key per event:
   - `event_type + subject_type + subject_id + recipient_user_id + period`
3. Tambahkan notification escalation:
   - Admin belum proses dalam SLA -> Manajer.
   - Manajer belum review dalam SLA -> Pengurus.
4. Tambahkan filter UI notifikasi berdasarkan kategori dan severity.

Acceptance:

- Approval pending muncul di role yang tepat.
- Exception tidak spam: satu event per subject per periode SLA.
- Pengurus menerima critical alert untuk NPL, SHU revision, dan outbox failure.

## Phase 3 - UI Notifikasi Kanan Atas dan Halaman Detail

Target: notifikasi kanan atas benar-benar usable.

Tasks:

1. Rework `NotificationIcon.vue`:
   - recent endpoint.
   - skeleton/loading state.
   - empty state.
   - unread badge.
   - role-aware link.
   - action click marks read then navigates.
2. Rework `/notifications` internal:
   - tabs: Semua, Belum Dibaca, Approval, Transaksi, Exception.
   - filter category/severity.
   - bulk mark read.
3. Rework `/member/notifications`:
   - mobile-first.
   - category chips: Simpanan, Pinjaman, POS, Reward, SHU.
4. Tambahkan polling 30 detik atau use Inertia reload only notifications.
5. Persiapkan SSE/WebSocket sebagai future enhancement, bukan Phase 3 blocker.

Acceptance:

- Ikon kanan atas berfungsi untuk Anggota dan internal role.
- Halaman notifikasi internal dan member menggunakan kontrak payload yang sama.
- Tidak ada link mati dari action URL.

## Phase 4 - Push, WhatsApp, dan Preference

Target: channel tambahan aktif setelah in-app stabil.

Tasks:

1. Aktifkan preference per category.
2. Pastikan `NotificationOutboxService` mendukung database + push + WhatsApp untuk event koperasi.
3. Gunakan push untuk:
   - Anggota: jatuh tempo, status pinjaman, status pembayaran, reward, coffee ready.
   - Internal: approval penting dan exception.
4. Gunakan WhatsApp secara selektif:
   - Anggota: jatuh tempo iuran/angsuran, approval/rejection penting.
   - Pengurus: critical exceptions.
5. Tambahkan retry, backoff, dan failed monitoring.

Acceptance:

- User dapat mengatur channel per kategori.
- Outbox failure terlihat di monitoring dan mengirim alert ke Pengurus.
- Push/WhatsApp tidak dikirim jika preference disabled.

## Phase 5 - Observability, Analytics, dan Governance

Target: notifikasi bisa dioperasikan secara production-grade.

Tasks:

1. Dashboard metrik:
   - sent count per channel.
   - failed count per channel.
   - unread aging.
   - top event types.
2. Data retention:
   - database notifications: 180 hari default.
   - push logs: 30 hari.
   - outbox failed: 90 hari atau sampai resolved.
3. Admin tools:
   - resend failed outbox.
   - mark failed as ignored with reason.
   - export notification audit.
4. Audit:
   - record event source and actor.
   - record delivery channel status.

Acceptance:

- Pengurus/System Admin bisa melihat kesehatan notifikasi.
- Failed notification dapat diretry tanpa tinker/manual SQL.
- Retention tidak menghapus audit transaksi utama.

## Prioritas Implementasi

1. P0: Ikon kanan atas + kontrak API standar.
2. P0: Pinjaman dan pembayaran simpanan karena langsung terkait workflow approval.
3. P1: Onboarding anggota, POS, reward, coffee order.
4. P1: Exception dan SLA escalation.
5. P2: Push/WhatsApp preference.
6. P2: Observability dan admin resend tooling.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Duplicate notification akibat retry | User menerima spam | Deduplication key dan unique constraint |
| Notifikasi role salah | Data sensitif bocor | Recipient resolver berbasis permission + organization |
| Dropdown kanan atas lambat | UX buruk | Endpoint `recent` minimal payload dan pagination kecil |
| WhatsApp/push gagal | Info penting tidak sampai | Database notification tetap source of truth |
| Link action berubah | Notifikasi membuka 404 | Gunakan named route/Wayfinder untuk action URL |

## Test Plan

Feature tests:

- User hanya melihat notifikasinya sendiri.
- Anggota menerima notifikasi status pembayaran/pinjaman sendiri.
- Admin Koperasi menerima pending payment dan operational queue.
- Manajer Koperasi menerima loan review required.
- Pengurus Koperasi menerima final approval required.
- Mark read dan mark all read bekerja untuk session dan API token.
- Preferences memblokir channel push/WhatsApp saat disabled.
- Duplicate event tidak membuat notifikasi ganda.

Frontend/build:

- `npm run build`
- Smoke test dropdown notification di role Anggota, Admin Koperasi, Manajer Koperasi, Pengurus Koperasi.

Backend:

- `php artisan test --compact tests/Feature/NotificationSystemTest.php`
- Tambah suite baru: `tests/Feature/Cooperative/CooperativeNotificationWorkflowTest.php`
