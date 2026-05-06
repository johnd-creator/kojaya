# KojayaPro & Kojayaku - API Documentation

## 📡 API Overview

KojayaPro dan Kojayaku menyediakan **RESTful API** yang lengkap untuk integrasi mobile applications dan third-party systems.

**Base URL:** `http://localhost:8000/api` (development)
**API Version:** v1
**Authentication:** Laravel Sanctum (Token-based)
**Content-Type:** `application/json`

---

## 🎯 API Platforms

### **KojayaPro API** (Sistem Admin)
API untuk admin panel KojayaPro - ERP, POS, Inventori, Akuntansi, Simpan Pinjam.

### **Kojayaku API** (Aplikasi Anggota)
API untuk mobile/web app Kojayaku - Simpanan, Pinjaman, Poin, Transaksi, Profil.

### **Mobile Integrations**
- **Kojayaku Member App** - `/api/v1/member/*`, `/api/v1/loans`, `/api/v1/points`, `/api/v1/rewards`
- **Technician App** - `/api/technician/*`
- **ESS App** - `/api/ess/*`, `/api/employees/{id}/*`

---

## 🔐 Authentication

### **Token Authentication Flow**

#### 1. **Login & Get Token**
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your_password",
  "app": "member",
  "device_name": "Android Phone",
  "device_id": "device-uuid"
}
```

**Response (200):**
```json
{
  "token_type": "Bearer",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "abilities": ["profile:read", "member:read", "member:write", "cooperative:read", "cooperative:write"],
  "user": {
    "id": "uuid",
    "name": "User Name",
    "email": "user@example.com",
    "roles": ["Anggota"],
    "employee_id": null,
    "cooperative_member_id": 1
  }
}
```

#### 2. **Use Token in Requests**
```http
GET /api/user
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

#### 3. **Logout (Revoke Token)**
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### 4. **Logout All Devices**
```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

#### 5. **Current Mobile Session**
```http
GET /api/auth/session
Authorization: Bearer {token}
```

#### 6. **Rotate Current Token**
```http
POST /api/token/rotate
Authorization: Bearer {token}
Content-Type: application/json

{
  "device_name": "Android Phone"
}
```

---

## 👤 User & Profile API

### **Get Current User**
```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": "uuid",
  "name": "John Doe",
  "email": "john@example.com",
  "roles": ["Employee"],
  "organization": {
    "id": "uuid",
    "name": "Koperasi Karyawan"
  },
  "permissions": ["attendance.check-in", "leaves.create"]
}
```

---

## 👥 Kojayaku Member Self-Service API

**Base Path:** `/api/v1/member`

All endpoints require `auth:sanctum`. Read endpoints require `member:read`; write endpoints require `member:write`.

### **Dashboard**
```http
GET /api/v1/member/dashboard
Authorization: Bearer {token}
```

Returns member profile, savings balance, pending invoices, active loans, loan outstanding, points balance, tier, and unread notifications.

### **Profile**
```http
GET /api/v1/member/profile
PUT /api/v1/member/profile
Authorization: Bearer {token}
```

### **Savings**
```http
GET /api/v1/member/savings/summary
GET /api/v1/member/savings/ledger?start_date=2026-01-01&end_date=2026-05-31
Authorization: Bearer {token}
```

`summary` returns total balance, grouped ledger totals, total approved payments, pending invoice count, and remaining unpaid invoice amount. `ledger` returns statement entries with running balance.

### **Dues and Payments**
```http
GET /api/v1/member/dues/invoices
GET /api/v1/member/payments
POST /api/v1/member/payments/proof
POST /api/payments/charge
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Payment proof fields: `cooperative_dues_invoice_id`, `amount`, `payment_method` (`TRANSFER` or `QRIS`), `paid_at`, optional `reference_no`, optional `notes`, and `proof` (`jpg`, `png`, or `pdf`).

Payment charge fields: `cooperative_payment_id` and `channel` (`QRIS`, `VA`, `E_WALLET`, or `TRANSFER`). The endpoint returns gateway provider, reference, status, amount, and checkout URL. Gateway webhook callback is `POST /api/payments/webhook` with `reference`/`gateway_reference`, `status`, and optional `reconciliation_reference`.

### **Push Device Registration**
```http
POST /api/devices/push-token
Authorization: Bearer {token}
```

Fields: `app`, `device_id`, `platform`, and `push_token`. The backend stores active mobile device tokens and currently mirrors push events to database notifications while integration providers are configured.

### **Loans**
```http
GET /api/v1/member/loans
POST /api/v1/member/loans
GET /api/v1/member/loans/{loan}
Authorization: Bearer {token}
```

Loan applications reuse the cooperative loan calculator/service and return the generated installment schedule. Members can only access loans linked to their own member profile.

### **SHU, Notifications, and Support**
```http
GET /api/v1/member/shu
GET /api/v1/member/notifications
GET /api/v1/member/support-tickets
POST /api/v1/member/support-tickets
Authorization: Bearer {token}
```

Support ticket fields: `subject`, `message`, optional `category` (`GENERAL`, `PAYMENT`, `LOAN`, `SAVINGS`, `POINTS`, `PROFILE`, `POS`), and optional `priority` (`LOW`, `NORMAL`, `HIGH`, `URGENT`).

---

## 🔧 Technician Work Orders API

**Base Path:** `/api/technician`

### **List Work Orders**
```http
GET /api/technician/work-orders?status=OPEN&priority=HIGH&scheduled_date=2026-05-06&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): `OPEN`, `IN_PROGRESS`, `COMPLETED`, `CLOSED`
- `priority` (optional): `LOW`, `MEDIUM`, `HIGH`, `EMERGENCY`
- `scheduled_date` (optional): `YYYY-MM-DD`
- `per_page` (optional): page size, default `15`
- `page` (optional): page number

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "title": "Maintenance AC Central",
      "description": "Regular maintenance",
      "status": "OPEN",
      "priority": "HIGH",
      "scheduled_date": "2026-05-05",
      "asset": {
        "id": "uuid",
        "name": "AC Central",
        "location": "Building A"
      },
      "checklists": [
        {
          "id": "uuid",
          "item": "Check filter condition",
          "completed": false
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  }
}
```

### **Get Work Order Detail**
```http
GET /api/technician/work-orders/{id}
Authorization: Bearer {token}
```

### **Start Work Order**
```http
POST /api/technician/work-orders/{id}/start
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 12
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "IN_PROGRESS",
    "started_at": "2026-05-02T08:00:00Z"
  }
}
```

### **Complete Work Order**
```http
POST /api/technician/work-orders/{id}/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10,
  "notes": "Pekerjaan selesai dan mesin normal"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "COMPLETED",
    "completed_at": "2026-05-02T17:00:00Z",
    "completion_latitude": "-6.2088000",
    "completion_longitude": "106.8456000",
    "completion_notes": "Pekerjaan selesai dan mesin normal"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot complete. 3 checklist items are pending."
}
```

### **Update Checklist Item**
```http
POST /api/technician/work-orders/{id}/checklists/{checklistId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "is_checked": true,
  "notes": "Filter replaced with new one"
}
```

### **Upload Work Order Evidence**
```http
POST /api/technician/work-orders/{id}/attachments
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Fields: `type` (`BEFORE`, `AFTER`, `OTHER`), `file` (`jpg`, `png`, or `pdf`), optional `latitude`, `longitude`, `accuracy`, and `notes`.

### **Record Spare Part Usage**
```http
POST /api/technician/work-orders/{id}/parts
Authorization: Bearer {token}
Content-Type: application/json

{
  "spare_part_id": "uuid",
  "warehouse_id": "uuid",
  "quantity_used": 2,
  "notes": "Ganti seal"
}
```

### **Offline Sync**
```http
POST /api/technician/work-orders/{id}/sync
Authorization: Bearer {token}
Content-Type: application/json

{
  "idempotency_key": "offline-001",
  "checklists": [
    {"id": "uuid", "is_checked": true, "notes": "Checked offline"}
  ],
  "parts": [
    {"spare_part_id": "uuid", "warehouse_id": "uuid", "quantity_used": 1}
  ],
  "completion": {
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 10,
    "notes": "Completed offline"
  }
}
```

Repeated submit with the same `idempotency_key` for the same technician and work order returns the stored response and does not duplicate parts/checklist actions.

### **Timeline, Escalation, and Reopen**
```http
GET /api/technician/work-orders/{id}/timeline
POST /api/technician/work-orders/{id}/escalate
POST /api/technician/work-orders/{id}/reopen
Authorization: Bearer {token}
```

Escalation fields: `type` (`BLOCKED`, `NEED_PART`, `NEED_SUPERVISOR`, `REASSIGNMENT`, `SAFETY_RISK`, `OTHER`), `reason`, optional `reassignment_requested_to`. Reopen requires `work-orders:review` ability and moves completed/closed work orders back to `IN_PROGRESS`.

---

## 👥 Cooperative Members API

**Base Path:** `/api/v1/members`

### **List Members**
```http
GET /api/v1/members
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search by name, code
- `status` (optional): `ACTIVE`, `INACTIVE`, `RESIGNED`
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "member_code": "KOP001",
      "name": "Ahmad Subarjo",
      "join_date": "2020-01-15",
      "status": "ACTIVE",
      "phone": "08123456789",
      "address": "Jl. Merdeka No. 10"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120
  }
}
```

### **Create Member**
```http
POST /api/v1/members
Authorization: Bearer {token}
Content-Type: application/json

{
  "member_code": "KOP002",
  "name": "Siti Aminah",
  "join_date": "2026-05-02",
  "phone": "08198765432",
  "address": "Jl. Sudirman No. 20",
  "email": "siti@example.com"
}
```

**Response (201):**
```json
{
  "data": {
    "id": "uuid",
    "member_code": "KOP002",
    "name": "Siti Aminah",
    "status": "ACTIVE",
    "created_at": "2026-05-02T10:00:00Z"
  }
}
```

### **Get Member Detail**
```http
GET /api/v1/members/{member}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": "uuid",
    "member_code": "KOP001",
    "name": "Ahmad Subarjo",
    "status": "ACTIVE",
    "documents": [...],
    "invoices": [...],
    "ledger": {
      "total_contributions": 5000000,
      "total_payments": 4500000,
      "balance": 500000
    }
  }
}
```

### **Update Member**
```http
PUT /api/v1/members/{member}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Ahmad Subarjo Updated",
  "phone": "08123456789",
  "address": "New Address"
}
```

### **Activate Member**
```http
POST /api/v1/members/{member}/activate
Authorization: Bearer {token}
```

### **Resign Member**
```http
POST /api/v1/members/{member}/resign
Authorization: Bearer {token}
Content-Type: application/json

{
  "resign_date": "2026-05-02",
  "reason": "Pindah domisili"
}
```

---

## 💰 Cooperative Dues API

**Base Path:** `/api/v1/dues`

### **List Invoices**
```http
GET /api/v1/dues/invoices
Authorization: Bearer {token}
```

**Query Parameters:**
- `member_id` (optional): Filter by member
- `status` (optional): `PENDING`, `PAID`, `OVERDUE`
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "invoice_number": "INV-2026-001",
      "member": {
        "id": "uuid",
        "name": "Ahmad Subarjo",
        "member_code": "KOP001"
      },
      "amount": 500000,
      "due_date": "2026-05-10",
      "status": "PENDING",
      "created_at": "2026-05-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 200
  }
}
```

### **Generate Dues**
```http
POST /api/v1/dues/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "period": "2026-05",
  "contribution_type": "IURAN_WAJIB",
  "due_date": "2026-05-10"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Generated 120 invoices",
  "data": {
    "period": "2026-05",
    "total_amount": 60000000,
    "total_members": 120
  }
}
```

### **Submit Payment**
```http
POST /api/v1/dues/payments
Authorization: Bearer {token}
Content-Type: application/json

{
  "cooperative_member_id": "uuid",
  "amount": 500000,
  "payment_method": "CASH",
  "paid_at": "2026-05-05",
  "reference_no": "REF-001",
  "notes": "Pembayaran iuran Mei"
}
```

**Response (201):**
```json
{
  "data": {
    "id": "uuid",
    "member": {
      "name": "Ahmad Subarjo"
    },
    "amount": 500000,
    "payment_method": "CASH",
    "status": "PENDING",
    "paid_at": "2026-05-05"
  }
}
```

### **Approve Payment**
```http
POST /api/v1/dues/payments/{payment}/approve
Authorization: Bearer {token}
```

---

## 🛒 POS API

**Base Path:** `/api/v1/pos`

### **List Products**
```http
GET /api/v1/pos/products
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search by name, SKU, barcode
- `category_id` (optional): Filter by category
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "sku": "PROD-001",
      "barcode": "8991001",
      "name": "Minyak Goreng 2L",
      "category": {
        "id": "uuid",
        "name": "Sembako"
      },
      "price": 35000,
      "stock": 50,
      "unit": "Pcs",
      "status": "ACTIVE"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

### **Create Transaction**
```http
POST /api/v1/pos/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_method": "CASH",
  "cooperative_member_id": null,
  "discount_amount": 0,
  "items": [
    {
      "pos_product_id": "uuid",
      "quantity": 2
    },
    {
      "pos_product_id": "uuid",
      "quantity": 1
    }
  ]
}
```

**Response (201):**
```json
{
  "data": {
    "id": "uuid",
    "transaction_number": "POS-2026-05001",
    "total_amount": 105000,
    "payment_method": "CASH",
    "items": [
      {
        "product": "Minyak Goreng 2L",
        "quantity": 2,
        "unit_price": 35000,
        "subtotal": 70000
      }
    ],
    "created_at": "2026-05-02T14:30:00Z"
  }
}
```

---

## 💰 Kojayaku - Savings (Simpanan) API

**Base Path:** `/api/v1/savings`
**Use Case:** Anggota cek saldo simpanan dan riwayat transaksi

### **Get Savings Balance**
```http
GET /api/v1/savings/balance
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "member_id": "uuid",
    "member_name": "Ahmad Subarjo",
    "total_savings": 15000000,
    "savings_accounts": [
      {
        "id": "uuid",
        "account_type": "SIMPANAN_POKOK",
        "account_name": "Simpanan Pokok",
        "balance": 500000,
        "created_at": "2025-01-15T00:00:00Z"
      },
      {
        "id": "uuid",
        "account_type": "SIMPANAN_WAJIB",
        "account_name": "Simpanan Wajib",
        "balance": 1000000,
        "created_at": "2025-01-15T00:00:00Z"
      },
      {
        "id": "uuid",
        "account_type": "SIMPANAN_SUKARELA",
        "account_name": "Simpanan Sukarela",
        "balance": 13500000,
        "created_at": "2025-01-15T00:00:00Z"
      }
    ]
  }
}
```

### **Get Savings Ledger (Riwayat Transaksi)**
```http
GET /api/v1/savings/ledger
Authorization: Bearer {token}
```

**Query Parameters:**
- `savings_account_id` (optional): Filter by account type
- `transaction_type` (optional): `DEPOSIT`, `WITHDRAWAL`
- `start_date` (optional): Filter start date (ISO 8601)
- `end_date` (optional): Filter end date (ISO 8601)
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "transaction_date": "2026-05-01T10:30:00Z",
      "transaction_type": "DEPOSIT",
      "amount": 500000,
      "balance_before": 14000000,
      "balance_after": 14500000,
      "description": "Setoran bulan Mei",
      "reference_number": "SAV-2026-05001"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

### **Download Savings Statement (PDF)**
```http
GET /api/v1/savings/statement/pdf
Authorization: Bearer {token}
```

**Query Parameters:**
- `start_date` (required): Start date
- `end_date` (required): End date
- `savings_account_id` (optional): Filter by account

**Response:** PDF file download

---

## 💸 Kojayaku - Loans (Pinjaman) API

**Base Path:** `/api/v1/loans`
**Use Case:** Anggota ajukan pinjaman dan cek status angsuran

### **List Loans**
```http
GET /api/v1/loans
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): `PENDING`, `APPROVED`, `REJECTED`, `DISBURSED`, `COMPLETED`
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "loan_number": "PINJ-2026-001",
      "loan_type": "PINJAMAN_UMUM",
      "principal_amount": 10000000,
      "interest_rate": 12,
      "tenure_months": 12,
      "monthly_installment": 888000,
      "disbursed_date": "2026-01-15",
      "status": "ACTIVE",
      "remaining_balance": 5500000
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5
  }
}
```

### **Apply for Loan (Ajukan Pinjaman)**
```http
POST /api/v1/loans/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "loan_type_id": "uuid",
  "amount": 10000000,
  "tenure_months": 12,
  "purpose": "Modal usaha warung",
  "guarantor_id": "uuid"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Pengajuan pinjaman berhasil dibuat. Menunggu persetujuan.",
  "data": {
    "id": "uuid",
    "loan_number": "PINJ-2026-001",
    "status": "PENDING",
    "estimated_monthly_installment": 888000,
    "created_at": "2026-05-02T10:00:00Z"
  }
}
```

### **Get Loan Detail**
```http
GET /api/v1/loans/{loan}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "loan_number": "PINJ-2026-001",
    "loan_type": "PINJAMAN_UMUM",
    "principal_amount": 10000000,
    "interest_rate": 12,
    "tenure_months": 12,
    "monthly_installment": 888000,
    "total_amount": 10656000,
    "disbursed_date": "2026-01-15",
    "next_payment_date": "2026-06-15",
    "status": "ACTIVE",
    "remaining_balance": 5500000,
    "installments_paid": 6,
    "installments_remaining": 6
  }
}
```

### **Get Installment Schedule (Jadwal Angsuran)**
```http
GET /api/v1/loans/{loan}/schedule
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "installment_number": 1,
      "due_date": "2026-02-15",
      "amount": 888000,
      "principal": 779000,
      "interest": 109000,
      "balance_after": 9221000,
      "status": "PAID",
      "paid_date": "2026-02-14"
    },
    {
      "installment_number": 7,
      "due_date": "2026-08-15",
      "amount": 888000,
      "principal": 779000,
      "interest": 109000,
      "balance_after": 4712000,
      "status": "PENDING"
    }
  ]
}
```

### **Loan Calculator (Simulasi Cicilan)**
```http
POST /api/v1/loans/calculate
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 10000000,
  "tenure_months": 12,
  "loan_type_id": "uuid"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "principal_amount": 10000000,
    "interest_rate": 12,
    "tenure_months": 12,
    "monthly_installment": 888000,
    "total_amount": 10656000,
    "total_interest": 656000
  }
}
```

---

## 🎁 Kojayaku - Points & Rewards API

**Base Path:** `/api/v1/points`
**Use Case:** Anggota cek poin dan tukar reward

### **Get Points Balance**
```http
GET /api/v1/points/balance
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "member_id": "uuid",
    "member_name": "Ahmad Subarjo",
    "total_points": 2500,
    "points_earned": 5000,
    "points_redeemed": 2500,
    "member_tier": "GOLD",
    "next_tier": "PLATINUM",
    "points_to_next_tier": 2500
  }
}
```

### **Get Points History**
```http
GET /api/v1/points/history
Authorization: Bearer {token}
```

**Query Parameters:**
- `transaction_type` (optional): `EARNED`, `REDEEMED`
- `start_date` (optional): Filter start date
- `end_date` (optional): Filter end date
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "transaction_date": "2026-05-01T14:30:00Z",
      "transaction_type": "EARNED",
      "points": 100,
      "balance_before": 2400,
      "balance_after": 2500,
      "description": "Belanja di toko koperasi",
      "reference_number": "POS-2026-05001"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

### **List Available Rewards**
```http
GET /api/v1/rewards
Authorization: Bearer {token}
```

**Query Parameters:**
- `category` (optional): `BARANG`, `DISKON`, `LAYANAN`
- `min_points` (optional): Minimum points required
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Voucher Belanja Rp 50.000",
      "description": "Voucher belanja di toko koperasi",
      "category": "DISKON",
      "points_required": 500,
      "stock": 50,
      "valid_until": "2026-12-31",
      "image_url": "https://example.com/rewards/voucher-50k.jpg"
    },
    {
      "id": "uuid",
      "name": "Rice Cooker",
      "description": "Rice cooker merek XXX",
      "category": "BARANG",
      "points_required": 2000,
      "stock": 10,
      "valid_until": "2026-12-31",
      "image_url": "https://example.com/rewards/rice-cooker.jpg"
    }
  ]
}
```

### **Redeem Reward (Tukar Poin)**
```http
POST /api/v1/rewards/{reward}/redeem
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 1,
  "delivery_address": "Jl. Contoh No. 123, Jakarta"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Penukaran reward berhasil. Silakan tunggu konfirmasi admin.",
  "data": {
    "id": "uuid",
    "reward": {
      "name": "Rice Cooker"
    },
    "points_used": 2000,
    "quantity": 1,
    "status": "PENDING",
    "created_at": "2026-05-02T10:00:00Z"
  }
}
```

---

## 🛒 Kojayaku - Transactions API

**Base Path:** `/api/v1/transactions`
**Use Case:** Anggota lihat riwayat belanja di toko koperasi

### **Get Transaction History**
```http
GET /api/v1/transactions
Authorization: Bearer {token}
```

**Query Parameters:**
- `start_date` (optional): Filter start date
- `end_date` (optional): Filter end date
- `payment_method` (optional): `CASH`, `TRANSFER`, `QRIS`, `MEMBER_CREDIT`
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "transaction_number": "POS-2026-05001",
      "transaction_date": "2026-05-01T14:30:00Z",
      "total_amount": 105000,
      "payment_method": "CASH",
      "points_earned": 105,
      "items": [
        {
          "product_name": "Minyak Goreng 2L",
          "quantity": 2,
          "unit_price": 35000,
          "subtotal": 70000
        },
        {
          "product_name": "Gula Pasir 1kg",
          "quantity": 1,
          "unit_price": 15000,
          "subtotal": 15000
        }
      ],
      "cashier": "Siti Aminah"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

### **Get Transaction Detail**
```http
GET /api/v1/transactions/{transaction}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "transaction_number": "POS-2026-05001",
    "transaction_date": "2026-05-01T14:30:00Z",
    "total_amount": 105000,
    "payment_method": "CASH",
    "discount_amount": 0,
    "final_amount": 105000,
    "points_earned": 105,
    "items": [
      {
        "product": {
          "name": "Minyak Goreng 2L",
          "sku": "PROD-001"
        },
        "quantity": 2,
        "unit_price": 35000,
        "subtotal": 70000
      }
    ],
    "cashier": {
      "name": "Siti Aminah"
    },
    "store": {
      "name": "Koperasi KOJAYA - Cabang Pusat"
    }
  }
}
```

---

## 👤 Employee Self Service API

**Base Path:** `/api/ess` and `/api/employees/{id}`
**Auth:** Sanctum bearer token
**Abilities:** `ess:read`, `ess:write`, `attendance:read`, `attendance:write`

### **Dashboard & Profile**
```http
GET /api/ess/dashboard
GET /api/ess/profile
PUT /api/ess/profile
Authorization: Bearer {token}
```

Dashboard mengembalikan profil karyawan, absensi hari ini, shift hari ini, statistik cuti/lembur/reimbursement, payroll terakhir, dan status compliance ringkas.

### **Attendance Check-In**
```http
POST /api/ess/attendance/check-in
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "device_id": "device-uuid"
}
```

**Response (200):**
```json
{
  "ok": true,
  "data": {
    "id": 1,
    "employee_id": 10,
    "date": "2026-05-06T00:00:00.000000Z",
    "clock_in": "08:00:00",
    "clock_in_latitude": "-6.2088000",
    "clock_in_longitude": "106.8456000",
    "clock_in_accuracy": "10.50",
    "clock_in_device_id": "device-uuid",
    "status": "PRESENT",
    "mobile_audit": {
      "check_in": {
        "at": "2026-05-06T08:00:00+07:00",
        "device_id": "device-uuid"
      }
    }
  }
}
```

**Error Response (422):**
```json
{
  "ok": false,
  "error": "Location outside geofence. Distance: 1.2km from office."
}
```

### **Attendance Today & History**
```http
GET /api/ess/attendance/today
GET /api/ess/attendance/history?per_page=15
Authorization: Bearer {token}
```

### **Attendance Check-Out**
```http
POST /api/ess/attendance/check-out
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "device_id": "device-uuid"
}
```

Check-out menyimpan `clock_out_latitude`, `clock_out_longitude`, `clock_out_accuracy`, `clock_out_device_id`, dan audit mobile.

### **Get Geofence Data**
```http
GET /api/ess/geofence
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "latitude": -6.2088,
    "longitude": 106.8456,
    "radius": 500
  }
}
```

### **Shift Roster**
```http
GET /api/ess/shift-roster?from=2026-05-06&to=2026-05-20
Authorization: Bearer {token}
```

Mengembalikan roster berdasarkan `employee.shift_group` dan relasi `workShift`.

### **Leave Requests**
```http
GET /api/ess/leaves?per_page=15
POST /api/ess/leaves
POST /api/ess/leaves/{leave}/cancel
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Create payload:
```json
{
  "leave_type_id": 1,
  "start_date": "2026-05-10",
  "end_date": "2026-05-12",
  "reason": "Attending family event",
  "attachment": "optional jpg/png/pdf"
}
```

List response menyertakan `balance` per jenis cuti. Cancellation menyimpan `cancel_requested_at`, `cancel_requested_by`, dan `cancel_reason`; status approval lama tetap mengikuti constraint `Pending`, `Approved`, `Rejected`.

### **Overtime**
```http
GET /api/ess/overtime?per_page=15
POST /api/ess/overtime
Authorization: Bearer {token}
Content-Type: application/json
```

Create payload:
```json
{
  "overtime_rule_id": 1,
  "date": "2026-05-06",
  "start_time": "18:00",
  "end_time": "20:00",
  "reason": "Closing bulanan"
}
```

### **Reimbursements**
```http
GET /api/ess/reimbursements?per_page=15
POST /api/ess/reimbursements
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Create payload memakai `items[]` dengan `category`, `description`, `amount`, `receipt_date`, dan `receipt_file` opsional. Semua reimbursement scoped ke user pemilik token.

### **Payslips, Compliance, Notifications**
```http
GET /api/ess/payslips?per_page=12
GET /api/ess/payslips/{payroll}/download
GET /api/ess/compliance
GET /api/ess/notifications?per_page=15
Authorization: Bearer {token}
```

Payslip hanya mengembalikan atau mengunduh payroll milik employee yang statusnya `PROCESSED` atau `PAID`. Compliance mengembalikan certificate dan medical checkup milik employee. Notifications memakai Laravel database notifications milik user login.

---

## 📊 Reports API

**Base Path:** `/api/reports`

### **Certificate Compliance Report**
```http
GET /api/reports/certificate-compliance
Authorization: Bearer {token}
```

**Query Parameters:**
- `department_id` (optional): Filter by department

**Response (200):**
```json
{
  "data": {
    "total_employees": 100,
    "compliant": 85,
    "non_compliant": 15,
    "compliance_percentage": 85.0,
    "expiring_soon": [
      {
        "employee": "John Doe",
        "certificate": "First Aid",
        "expiry_date": "2026-06-01",
        "days_remaining": 30
      }
    ]
  }
}
```

### **MCU Compliance Report**
```http
GET /api/reports/mcu-compliance
Authorization: Bearer {token}
```

### **Non-Compliant Employees**
```http
GET /api/reports/non-compliant-employees
Authorization: Bearer {token}
```

---

## 📋 Common Response Format

### **Success Response**
```json
{
  "success": true,
  "data": { ... }
}
```

### **Error Response**
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### **Pagination Response**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "https://api.com/resource?page=1",
    "last": "https://api.com/resource?page=7",
    "prev": null,
    "next": "https://api.com/resource?page=2"
  }
}
```

---

## 🔒 Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error |

---

## 🧪 Testing API

### **Using cURL**

```bash
# 1. Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# 2. Get work orders
curl -X GET http://localhost:8000/api/technician/work-orders \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# 3. Create transaction
curl -X POST http://localhost:8000/api/v1/pos/transactions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_method": "CASH",
    "items": [
      {"pos_product_id": "uuid", "quantity": 2}
    ]
  }'
```

---

## 📱 Mobile App Integration Guide

### **Android (Kotlin) Example**

```kotlin
// API Client
class ApiClient {
    private val baseUrl = "http://localhost:8000/api"
    private var token: String? = null

    suspend fun login(email: String, password: String): AuthResponse {
        // Implementation
    }

    suspend fun getWorkOrders(): List<WorkOrder> {
        // Requires token
    }
}

// Usage
val api = ApiClient()
val authResponse = api.login("user@example.com", "password")
api.token = authResponse.token

val workOrders = api.getWorkOrders()
```

### **Flutter (Dart) Example**

```dart
class ApiClient {
  final String baseUrl = 'http://localhost:8000/api';
  String? _token;

  Future<AuthResponse> login(String email, String password) async {
    // Implementation
  }

  Future<List<WorkOrder>> getWorkOrders() async {
    // Requires _token
  }
}
```

---

## 🔄 API Changelog

### **Version 1.0.0** (Current Release)
- Initial API release
- Technician work orders endpoints
- Cooperative members management
- Dues and payments endpoints
- POS products and transactions
- ESS attendance and leaves
- Certificate compliance reports
- Operator hardening endpoints for approval inbox, closing, reconciliation, exception dashboard, exports, monitoring, and OpenAPI.
- Production integration foundation: payment charge/webhook, push token registration, and `/api/openapi.json`.

### **Planned for v1.1.0**
- Payment gateway integration
- WhatsApp notifications
- Advanced filtering and sorting
- Export endpoints (Excel, PDF)
- Real-time updates (WebSocket/SSE)

---

*Last Updated: May 6, 2026*
