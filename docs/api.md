# KojayaPro & Kojayaku - API Documentation

## 📡 API Overview

KojayaPro dan Kojayaku menyediakan **RESTful API** yang lengkap untuk integrasi mobile applications dan third-party systems.

**Base URL:** `http://localhost:8000/api` (development)
**API Version:** v1
**Application Release:** `v0.1.0` (Internal Alpha; pending release preparation)
**API Contract Version:** `1.0.0`
**Authentication:** Laravel Sanctum (Token-based)
**Content-Type:** `application/json`
**OpenAPI Spec:** `GET /api/openapi.json`

The application release version and API contract version are intentionally
separate. `1.0.0` in the OpenAPI metadata identifies the API contract, not a
production application release.

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
  "abilities": ["profile:read", "member:read", "member:write"],
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

**App Parameter:**
- `member` - Kojayaku member app (abilities: `profile:read`, `member:read`, `member:write`)
- `ess` - Employee Self-Service (abilities: `ess:read`, `ess:write`, `attendance:read`, `attendance:write`, `payroll:read`)
- `technician` - Technician app (abilities: `work-orders:read`, `work-orders:write`)
- `admin` - Admin panel with granular cooperative, POS, and reporting abilities. New tokens never receive `*`; legacy wildcard or combined tokens must be explicitly rotated.

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

**Response (200):**
```json
{
  "message": "Logged out."
}
```

#### 4. **Logout All Devices**
```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "All tokens revoked."
}
```

#### 5. **Current Mobile Session**
```http
GET /api/auth/session
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "user": {
    "id": "uuid",
    "name": "User Name",
    "email": "user@example.com",
    "roles": ["Anggota"],
    "employee_id": null,
    "cooperative_member_id": 1
  },
  "token": {
    "name": "Android Phone",
    "abilities": ["profile:read", "member:read", "member:write"]
  }
}
```

#### 6. **Rotate Current Token**
```http
POST /api/token/rotate
Authorization: Bearer {token}
Content-Type: application/json

{
  "app": "member",
  "device_name": "Android Phone"
}
```

`app` is optional when the current token already has explicit `token_app`
metadata or can be classified as one exact legacy profile. It is required for
an unsafe legacy token (wildcard, combined, empty, or unknown abilities). The
selected app cannot elevate permissions; the new ability set is resolved from
the user's current permissions and the app profile. A safe legacy token cannot
be changed into another app profile during rotation.

**Response (200):**
```json
{
  "token_type": "Bearer",
  "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "abilities": ["profile:read", "member:read", "member:write"],
  "token_app": "member",
  "token_version": "v1",
  "expires_at": null
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

All endpoints require `auth:sanctum` and a `CooperativeMember` record linked to the authenticated user. Read endpoints require `member:read`; write endpoints require `member:write`.

### **Kojayaku Mobile Menu Endpoint Map**

| Menu | Primary API |
|------|-------------|
| Kojayaku / Beranda | `GET /api/v1/member/dashboard` |
| Simpanan | `GET /api/v1/member/savings/summary`, `GET /api/v1/member/savings/ledger`, `GET /api/v1/member/dues/invoices`, `GET /api/v1/member/payments` |
| Pinjaman | `GET /api/v1/member/loans`, `POST /api/v1/member/loans`, `GET /api/v1/member/loans/{loan}` |
| Poin Saya | `GET /api/v1/points/balance`, `GET /api/v1/points/history` |
| Rewards | `GET /api/v1/rewards`, `POST /api/v1/rewards/{reward}/redeem`, `GET /api/v1/member/reward-redemptions` |
| Transaksi | `GET /api/v1/member/transactions` |
| Pesan Kopi | `GET /api/v1/member/coffee/menu`, `POST /api/v1/member/coffee/orders`, `GET /api/v1/member/coffee/orders/{coffeeOrder}` |
| Profil | `GET /api/v1/member/profile`, `PUT /api/v1/member/profile` |

### **Dashboard**
```http
GET /api/v1/member/dashboard
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "member": {
      "id": "uuid",
      "member_code": "KOP001",
      "name": "Ahmad Subarjo",
      "status": "ACTIVE",
      "join_date": "2020-01-15"
    },
    "summary": {
      "savings_balance": 15000000,
      "pending_invoices": 2,
      "active_loans": 1,
      "loan_outstanding": 5500000,
      "points_balance": 2500,
      "member_tier": "GOLD",
      "unread_notifications": 3
    },
    "onboarding": {
      "completed_steps": 3,
      "total_steps": 4,
      "progress_percent": 75,
      "is_complete": false,
      "is_dismissed": false,
      "steps": [
        {
          "key": "profile",
          "label": "Lengkapi profil",
          "completed": true,
          "href": "/member/profile"
        }
      ]
    },
    "journeys": {
      "payment": {
        "title": "Status pembayaran",
        "current_status": "PENDING",
        "steps": []
      },
      "loan": {
        "title": "Status pinjaman",
        "current_status": "ACTIVE",
        "steps": []
      },
      "reward": {
        "title": "Status reward",
        "current_status": "PROCESSING",
        "steps": []
      }
    }
  }
}
```

### **Pesan Kopi**
```http
GET /api/v1/member/coffee/menu
POST /api/v1/member/coffee/orders
GET /api/v1/member/coffee/orders/{coffeeOrder}
Authorization: Bearer {token}
```

`coffee/menu` mengembalikan katalog produk POS yang cocok dengan kategori/nama kopi untuk screen Flutter `/anggota/kopi`.

**Order Request:**
```json
{
  "items": [
    {
      "pos_product_id": 1,
      "quantity": 2,
      "sugar_level": "Less Sugar",
      "ice_level": "Warm",
      "cup_size": "Large"
    },
    {
      "pos_product_id": 2,
      "quantity": 1,
      "sugar_level": "Normal",
      "ice_level": "Normal",
      "cup_size": "Reguler"
    }
  ],
  "client_reference": "mobile-uuid",
  "channel": "QRIS"
}
```

`coffee/orders` membuat `member_payment_intents` dan charge gateway terlebih dahulu. Response awal berstatus `PENDING_PAYMENT` dan berisi `payment_intent` + `charge`. Stok belum berkurang dan transaksi POS belum dibuat pada tahap ini. Setelah webhook gateway `PAID`, backend membuat transaksi POS, mengurangi stok, mencatat pembayaran, membuat `coffee_orders`, dan status tracker masuk `RECEIVED`.

`coffee/orders/{coffeeOrder}` mengembalikan status tracker terbaru milik anggota yang sedang login. Status yang valid: `RECEIVED`, `BREWING`, `READY`, `PICKED_UP`, `CANCELLED`. Admin Koperasi mengelola antrian melalui web route `/cooperative/pos/coffee-orders`.

### **Onboarding & Status Journey**
```http
GET /api/v1/member/onboarding/status
POST /api/v1/member/onboarding/steps
GET /api/v1/member/status-journey
Authorization: Bearer {token}
```

`onboarding/status` mengembalikan checklist onboarding anggota: kelengkapan profil, setoran simpanan pertama, intro pinjaman, dan intro reward. Langkah profil/simpanan dihitung dari data live anggota; langkah pinjaman/reward bisa ditandai saat anggota membuka fitur.

**Mark Onboarding Step Request:**
```json
{
  "step": "loans"
}
```

Nilai `step` yang valid: `profile`, `first_savings`, `loans`, `rewards`.

`status-journey` mengembalikan status ringkas dan timeline untuk pembayaran terakhir, pinjaman terakhir, dan redeem reward terakhir agar aplikasi Kojayaku bisa menampilkan perjalanan proses yang konsisten.

### **Profile**
```http
GET /api/v1/member/profile
PUT /api/v1/member/profile
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "user": {
      "id": "uuid",
      "name": "Ahmad Subarjo",
      "email": "ahmad@example.com"
    },
    "member": {
      "id": "uuid",
      "member_code": "KOP001",
      "status": "ACTIVE",
      "organization": {
        "id": "uuid",
        "name": "Koperasi Karyawan"
      }
    }
  }
}
```

### **Savings**
```http
GET /api/v1/member/savings/summary
GET /api/v1/member/savings/ledger?category=WAJIB&start_date=2026-01-01&end_date=2026-05-31&page=1&per_page=15
POST /api/v1/member/savings/withdraw
Authorization: Bearer {token}
```

**Summary Response (200):**
```json
{
  "data": {
    "total_balance": 15000000,
    "by_category": {
      "POKOK": 200000,
      "WAJIB": 600000,
      "SUKARELA": 300000,
      "KHUSUS": 150000
    },
    "uncategorized": 0,
    "total_paid": 14500000,
    "pending_invoices": 2,
    "pending_invoice_amount": 1000000
  }
}
```

**Withdrawal Request (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "cooperative_member_id": 1,
    "amount": "150000.00",
    "status": "PENDING",
    "destination_bank": "BCA",
    "destination_account_no": "1234567890",
    "destination_account_name": "Ahmad Subarjo",
    "reason": "Kebutuhan mendesak"
  }
}
```

**Ledger Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "entry_type": "SAVING_PAYMENT",
      "ledger_scope": "SAVINGS",
      "category": "WAJIB",
      "contribution_type": {
        "id": 2,
        "code": "WAJIB",
        "name": "Simpanan Wajib",
        "category": "WAJIB"
      },
      "description": "Setoran bulan Mei",
      "posted_at": "2026-05-01",
      "debit": 0,
      "credit": 500000,
      "balance_delta": 500000
    }
  ],
  "links": {},
  "meta": {}
}
```

### **Dues and Payments**
```http
GET /api/v1/member/dues/invoices?status=UNPAID&category=WAJIB&period=2026-05
GET /api/v1/member/payments?status=APPROVED&category=WAJIB&start_date=2026-01-01&end_date=2026-05-31
GET /api/v1/member/payments/{payment}/receipt
POST /api/v1/member/payments/proof
Authorization: Bearer {token}
```

**Payment Proof (multipart/form-data):**
```
cooperative_dues_invoice_id: uuid
amount: 500000
payment_method: TRANSFER
paid_at: 2026-05-05
reference_no: REF-001 (optional)
notes: Pembayaran iuran Mei (optional)
proof: <file> (jpg, png, or pdf, max 4MB)
```

**Response (201):**
```json
{
  "data": {
    "id": "uuid",
    "invoice": {
      "id": "uuid",
      "invoice_number": "INV-2026-001",
      "contribution_type": {
        "name": "Iuran Wajib"
      }
    },
    "amount": 500000,
    "payment_method": "TRANSFER",
    "status": "PENDING",
    "paid_at": "2026-05-05",
    "proof_url": "https://storage.example.com/proofs/xxx.jpg"
  }
}
```

**Payment Receipt Response (200):**
```json
{
  "data": {
    "receipt_no": "RC-202606-000001",
    "issued_at": "2026-06-12T10:15:00Z",
    "download_url": "https://example.com/download/cooperative-receipts/1?expires=..."
  }
}
```

Receipt hanya tersedia untuk pembayaran berstatus `APPROVED`. `download_url` adalah signed URL sementara untuk file PDF receipt.

### **Payment Gateway Charge**
Member app membuat QRIS Sandbox untuk invoice iuran/simpanan milik anggota melalui `CooperativePayment` yang sudah dibuat dari invoice. Settlement tetap masuk ke `CooperativePaymentService` dan ledger `SAVING_PAYMENT`.

```http
POST /api/v1/member/bills/{bill}/payment-intent
Authorization: Bearer {token}
Content-Type: application/json

{
  "channel": "QRIS"
}
```

Untuk native Midtrans Core API QRIS Sandbox, gunakan bill dues/savings dan channel `QRIS`.

**Response (201):**
```json
{
  "data": {
    "bill_id": "dues:123",
    "source": "dues",
    "payment": {
      "id": 99,
      "amount": 80000,
      "payment_method": "QRIS",
      "gateway_provider": "internal",
      "gateway_status": "PENDING",
      "status": "PENDING"
    },
    "charge": {
      "provider": "internal",
      "reference": "PAY-ABC123",
      "status": "PENDING",
      "channel": "QRIS",
      "amount": 80000,
      "checkout_url": "http://localhost:8000/api/payments/PAY-ABC123/checkout",
      "qr_image_url": "/api/v1/member/payments/99/qris-image",
      "expires_at": "2026-06-29T10:00:00Z",
      "instructions": {},
      "poll_after_seconds": 5
    }
  }
}
```

```http
POST /api/payments/charge
Authorization: Bearer {token}
Content-Type: application/json

{
  "cooperative_payment_id": "uuid",
  "channel": "QRIS"
}
```

**Phase B supported channel:** `QRIS`

**Response (201):**
```json
{
  "data": {
    "provider": "midtrans",
    "reference": "KOJ-99-ABCD1234",
    "status": "PENDING",
    "channel": "QRIS",
    "amount": 500000,
    "checkout_url": null,
    "qr_image_url": "/api/v1/member/payments/99/qris-image",
    "expires_at": "2026-06-29 10:00:00",
    "instructions": {
      "title": "Scan QRIS untuk membayar",
      "description": "Status pembayaran diperbarui setelah Midtrans mengonfirmasi transaksi."
    },
    "poll_after_seconds": 5
  }
}
```

Response charge bersifat provider-neutral untuk Flutter: tidak memuat Server Key, Client Key, raw credential, atau action URL Midtrans. Flutter mengambil gambar QR sebagai bytes melalui authenticated API client:

```http
GET /api/v1/member/payments/{payment}/qris-image
Authorization: Bearer {token}
Accept: image/png
```

Endpoint gambar QR hanya tersedia untuk pembayaran QRIS milik anggota yang sedang login. Backend membuat PNG dari `qr_string` server-side bila tersedia di payload charge, atau mengambil action `generate-qr-code-v2` / `generate-qr-code` dari payload Midtrans secara server-side.

Polling status payment:

```http
GET /api/v1/member/payments/{payment}/status
Authorization: Bearer {token}
```

```json
{
  "data": {
    "payment_id": 99,
    "status": "PENDING",
    "gateway_status": "PENDING",
    "reconciled_at": null,
    "gateway_expires_at": "2026-06-29T10:00:00+00:00",
    "is_paid": false,
    "is_failed": false,
    "is_terminal": false,
    "poll_after_seconds": 5
  }
}
```

Gateway status yang dibedakan: `PENDING`, `PAID`, `EXPIRED`, `CANCELLED`, dan `FAILED`.

### **Push Device Registration**
```http
POST /api/devices/push-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "app": "member",
  "device_id": "device-uuid",
  "platform": "android",
  "push_token": "fcm-token-string"
}
```

**Response (200):**
```json
{
  "data": {
    "id": "uuid",
    "app": "member",
    "device_id": "device-uuid",
    "platform": "android",
    "push_token": "fcm-token-string",
    "is_active": true
  }
}
```

### **Loans**
```http
GET /api/v1/member/loans
POST /api/v1/member/loans
GET /api/v1/member/loans/{loan}
POST /api/v1/member/loans/{loan}/restructure
Authorization: Bearer {token}
```

Loan applications reuse the cooperative loan calculator/service and return the generated installment schedule. Members can only access loans linked to their own member profile.

Loan status flow: `APPLIED` (waiting for `Manajer Koperasi` review) → `MANAGER_APPROVED` (waiting for final `Pengurus Koperasi` approval) → `APPROVED` (ready for disbursement) → `ACTIVE` (disbursed). `REJECTED`, `PAID_OFF`, `DEFAULTED`, and `WRITTEN_OFF` remain terminal/operational states depending on the loan lifecycle.

**Apply Loan Request:**
```json
{
  "loan_type_id": "uuid",
  "amount": 10000000,
  "tenure_months": 12,
  "purpose": "Modal usaha warung"
}
```

**Restructure Request (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "loan_id": 10,
    "cooperative_member_id": 1,
    "status": "PENDING",
    "reason": "Pendapatan turun sementara",
    "proposed_term_months": 18
  }
}
```

### **SHU (Sisa Hasil Usaha)**
```http
GET /api/v1/member/shu
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "period": "2025",
      "status": "CLOSED",
      "allocations": [
        {
          "type": "DIVIDEN",
          "amount": 500000
        },
        {
          "type": "JASA_BELANJA",
          "amount": 250000
        }
      ]
    }
  ]
}
```

### **Reward Redemptions**
```http
GET /api/v1/member/reward-redemptions?status=PENDING&per_page=15
Authorization: Bearer {token}
```

Returns paginated reward redemption history scoped to the authenticated member. Use this together with `GET /api/v1/rewards` and `POST /api/v1/rewards/{reward}/redeem` for the Rewards mobile page.

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "reward_id": "uuid",
      "reward": {
        "id": "uuid",
        "name": "Voucher Belanja",
        "category": "VOUCHER",
        "points_required": 500
      },
      "quantity": 2,
      "points_used": 1000,
      "status": "PENDING",
      "delivery_address": "Jl. Koperasi No. 1",
      "redeemed_at": "2026-06-10T10:00:00Z",
      "processed_at": null
    }
  ],
  "links": {},
  "meta": {}
}
```

### **Transactions**
```http
GET /api/v1/member/transactions?date_from=2026-06-01&date_to=2026-06-30&status=COMPLETED&per_page=15
Authorization: Bearer {token}
```

Returns POS purchase history scoped to the authenticated member. This endpoint powers the Kojayaku **Transaksi** page in the mobile app.

**Response (200):**
```json
{
  "summary": {
    "total_transactions": 1,
    "total_amount": 300000,
    "total_items": 2,
    "last_transaction_at": "2026-06-10T15:30:00Z"
  },
  "transactions": {
    "data": [
      {
        "id": 1,
        "transaction_no": "POS-20260610-001",
        "client_reference": null,
        "subtotal": 300000,
        "discount_amount": 0,
        "total_amount": 300000,
        "status": "COMPLETED",
        "sold_at": "2026-06-10T15:30:00Z",
        "cashier": {
          "id": "uuid",
          "name": "Kasir Koperasi"
        },
        "items": [
          {
            "id": 1,
            "product_id": 10,
            "product": {
              "id": 10,
              "name": "Beras Koperasi",
              "sku": "BR-KOP-001"
            },
            "quantity": 2,
            "unit_price": 150000,
            "line_total": 300000
          }
        ],
        "payments": [
          {
            "id": 1,
            "payment_method": "CASH",
            "amount": 300000,
            "reference_no": null
          }
        ]
      }
    ],
    "links": {},
    "meta": {}
  }
}
```

### **Notifications**
```http
GET /api/v1/member/notifications?per_page=15
Authorization: Bearer {token}
```

Returns paginated database notifications for the authenticated user.

### **Support Tickets**
```http
GET /api/v1/member/support-tickets
POST /api/v1/member/support-tickets
Authorization: Bearer {token}
```

**Create payload:**
```json
{
  "subject": "Masalah pembayaran iuran",
  "message": "Saya sudah bayar tapi status masih pending",
  "category": "PAYMENT",
  "priority": "HIGH"
}
```

**Category options:** `GENERAL`, `PAYMENT`, `LOAN`, `SAVINGS`, `POINTS`, `PROFILE`, `POS`
**Priority options:** `LOW`, `NORMAL`, `HIGH`, `URGENT`

---

## 🔧 Technician Work Orders API

**Base Path:** `/api/technician`

All endpoints require the user to be the assigned technician on the work order (403 otherwise). Supervisor endpoints require `work-orders:review` or `view_work_order_all` ability.

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

Returns work order with `asset`, `organization`, `checklists`, `parts`, `attachments`, and `timelines`.

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
  "message": "Work order started",
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
  "message": "Work order completed",
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

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "item": "Check filter condition",
    "is_checked": true,
    "notes": "Filter replaced with new one"
  }
}
```

### **Upload Work Order Evidence**
```http
POST /api/technician/work-orders/{id}/attachments
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Fields:**
- `type` (required): `BEFORE`, `AFTER`, `OTHER`
- `file` (required): jpg, jpeg, png, or pdf (max 8MB)
- `latitude` (optional): numeric
- `longitude` (optional): numeric
- `accuracy` (optional): numeric
- `notes` (optional): string

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "type": "BEFORE",
    "file_url": "https://storage.example.com/attachments/xxx.jpg",
    "latitude": "-6.2088000",
    "longitude": "106.8456000",
    "notes": "Kondisi awal"
  }
}
```

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

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "spare_part": {
      "id": "uuid",
      "name": "Seal Kit",
      "sku": "SP-001"
    },
    "warehouse": {
      "id": "uuid",
      "name": "Warehouse A"
    },
    "quantity_used": 2,
    "notes": "Ganti seal"
  }
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

**Response (200):**
```json
{
  "success": true,
  "data": {
    "idempotency_key": "offline-001",
    "updated_checklists": [...],
    "created_parts": [...],
    "work_order": {...}
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

**Escalation payload:**
```json
{
  "type": "NEED_PART",
  "reason": "Spare part tidak tersedia di warehouse",
  "reassignment_requested_to": "user-uuid"
}
```

**Escalation types:** `BLOCKED`, `NEED_PART`, `NEED_SUPERVISOR`, `REASSIGNMENT`, `SAFETY_RISK`, `OTHER`

**Reopen** requires `work-orders:review` ability and moves `COMPLETED`/`CLOSED` work orders back to `IN_PROGRESS`.

---

## 👥 Cooperative Members API

**Base Path:** `/api/v1/members`

### **List Members**
```http
GET /api/v1/members?search=ahmad&status=ACTIVE&page=1
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
    "organization": {
      "id": "uuid",
      "name": "Koperasi Karyawan"
    },
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

### **Resolve Account-Link Candidate**
```http
GET /api/v1/members/{member}/account-link/candidates?email=member@example.com
Authorization: Bearer {token}
```

The lookup is an exact email lookup scoped to the member's organization. It
returns only verified, ordinary users who are not already linked to another
cooperative member. It is not a user directory and does not perform linking.
Use the returned candidate ID with the dedicated account-link action and a
controlled reason code.

### **Link or Unlink Member Account**
```http
POST /api/v1/members/{member}/account-link
DELETE /api/v1/members/{member}/account-link
Authorization: Bearer {token}
Content-Type: application/json
```

Link requires `user_id` and a controlled `reason`; unlink requires only the
controlled `reason`. Both actions verify organization ownership, eligibility,
and authorization transactionally.

## 💰 Cooperative Dues API

**Base Path:** `/api/v1/dues`

### **List Invoices**
```http
GET /api/v1/dues/invoices?member_id=1&member_search=Ahmad&period=2026-05&status=UNPAID&category=WAJIB&contribution_type_id=2&page=1&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `member_id` (optional): Filter by member
- `member_search` (optional): Filter by member name or member number
- `period` (optional): `YYYY-MM`
- `status` (optional): `UNPAID`, `PARTIAL`, `PAID`, `VOID`
- `category` (optional): `POKOK`, `WAJIB`, `SUKARELA`, `KHUSUS`
- `contribution_type_id` (optional): Filter by contribution type
- `page` (optional): Page number
- `per_page` (optional): Page size

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
  "created": 120
}
```

### **Savings Categories**
```http
GET /api/v1/savings/categories
Authorization: Bearer {token}
```

Returns active contribution types grouped by standard savings categories: `POKOK`, `WAJIB`, `SUKARELA`, and `KHUSUS`.

### **Savings Ledger**
```http
GET /api/v1/savings/ledger?member_search=Ahmad&ledger_scope=SAVINGS&category=WAJIB&start_date=2026-01-01&end_date=2026-05-31
Authorization: Bearer {token}
```

Returns paginated savings ledger entries plus a `summary` object using the same `by_category` contract as the member savings summary.

### **Submit Payment**
```http
POST /api/v1/dues/payments
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "cooperative_member_id": "uuid",
  "cooperative_dues_invoice_id": "uuid",
  "cooperative_contribution_type_id": 2,
  "amount": 500000,
  "payment_method": "CASH",
  "paid_at": "2026-05-05",
  "reference_no": "REF-001",
  "notes": "Setoran simpanan wajib bulan Juni",
  "proof": "<file>"
}
```

**Rules:**
- `cooperative_dues_invoice_id` optional jika admin mencatat pembayaran langsung dari tagihan yang sudah ada.
- `cooperative_contribution_type_id` optional jika `cooperative_dues_invoice_id` dikirim; wajib bila admin mencatat setoran langsung berbasis jenis simpanan.
- Jenis simpanan admin dibatasi ke `POKOK`, `WAJIB`, dan `SUKARELA`.
- Jika hanya `cooperative_member_id` + `cooperative_contribution_type_id` yang dikirim, sistem otomatis menghubungkan pembayaran ke invoice `UNPAID`/`PARTIAL` paling awal yang cocok.
- `proof` optional, menerima gambar `jpg`, `jpeg`, atau `png` maksimal 4MB.
- `POKOK` harus `200000` dan `WAJIB` harus `100000`; `SUKARELA` bebas nominal.

**Response (201):**
```json
{
  "data": {
    "id": "uuid",
    "member": {
      "name": "Ahmad Subarjo"
    },
    "contribution_type": {
      "id": 2,
      "code": "WAJIB",
      "name": "Simpanan Wajib",
      "category": "WAJIB"
    },
    "invoice": {
      "invoice_number": "INV-2026-001"
    },
    "amount": 500000,
    "payment_method": "CASH",
    "status": "PENDING",
    "paid_at": "2026-05-05",
    "proof_path": "cooperative/payment-proofs/admin-api/example.jpg"
  }
}
```

### **Submit Batch Payment**
```http
POST /api/v1/dues/payments/batch
Authorization: Bearer {token}
Content-Type: application/json

{
  "invoice_ids": [1, 2, 3],
  "payment_method": "TRANSFER",
  "paid_at": "2026-06-04",
  "reference_no": "BATCH-20260604-01",
  "notes": "Batch payment operator"
}
```

**Response (201):**
```json
{
  "data": {
    "processed_count": 3,
    "total_amount": 150000,
    "payments": []
  }
}
```

### **Approve Payment**
```http
POST /api/v1/dues/payments/{payment}/approve
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": "uuid",
    "status": "PAID"
  }
}
```

---

## 🛒 POS API

**Base Path:** `/api/v1/pos`

### **List Products**
```http
GET /api/v1/pos/products?search=minyak&category_id=uuid
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

### **Create Return**
```http
POST /api/v1/pos/returns
Authorization: Bearer {token}
Content-Type: application/json

{
  "pos_transaction_id": 123,
  "reason": "Barang rusak",
  "items": [
    {
      "pos_transaction_item_id": 456,
      "quantity": 1
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "pos_transaction_id": 123,
    "return_no": "RET-20260516-120000-001",
    "status": "APPROVED",
    "total_amount": "20000.00",
    "points_reversed": 10
  }
}
```

### **POS Offline Sync (POS Mobile / Unreliable Network)**

Endpoint di bawah ini dipakai oleh aplikasi POS untuk meng-antrikan transaksi yang
dibuat saat mode offline. Server menjamin idempotency: payload yang sama dengan
idempotency_key yang sama akan di-replay, sedangkan payload berbeda akan
mengembalikan **409 Conflict**.

**Base Path:** `/api/v1/pos/sync`

#### **Get Catalog (katalog produk)**
```http
GET /api/v1/pos/sync/catalog
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "sku": "P-001",
      "barcode": "8991234567",
      "name": "Minyak Goreng 1L",
      "cost_price": 10000,
      "sale_price": 13000,
      "stock": 24,
      "image_path": "products/minyak-1l.png",
      "brand": "Sania",
      "variant": "1L",
      "unit": "btl"
    }
  ],
  "synced_at": "2026-06-13T12:00:00+07:00"
}
```

#### **Enqueue Sync Request**
```http
POST /api/v1/pos/sync/enqueue
Authorization: Bearer {token}
Content-Type: application/json

{
  "idempotency_key": "dev-A-20260613-001",
  "client_id": "device-A",
  "device_id": "DEVICE-A",
  "pos_cashier_shift_id": 7,
  "endpoint": "pos.transactions.store",
  "method": "POST",
  "payload": {
    "client_reference": "OFFLINE-001",
    "items": [
      { "pos_product_id": 1, "quantity": 2 }
    ],
    "payments": [
      { "payment_method": "CASH", "amount": 26000, "cash_received": 30000 }
    ]
  }
}
```

**Response (202) - new request:**
```json
{ "idempotency_key": "dev-A-20260613-001", "status": "PENDING" }
```

**Response (202) - replay (payload sama):**
Mengembalikan entry yang sama persis.

**Response (409) - conflict (key sama, payload berbeda):**
```json
{
  "message": "Idempotency key dipakai dengan payload berbeda.",
  "errors": { "idempotency_key": ["..."] }
}
```

**Response (422) - endpoint tidak didukung:**
```json
{ "errors": { "endpoint": ["Endpoint X belum didukung..."] } }
```

#### **Process Sync Request**
```http
POST /api/v1/pos/sync/process/{idempotency_key}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "idempotency_key": "dev-A-20260613-001",
  "status": 201,
  "data": { "id": 12, "transaction_no": "POS-..." },
  "replay": false
}
```

**Response (404):**
`Sync request tidak ditemukan atau bukan milik user`.

#### **Process Batch**
```http
POST /api/v1/pos/sync/batch
Authorization: Bearer {token}
Content-Type: application/json

{ "idempotency_keys": ["dev-A-20260613-001", "dev-A-20260613-002"] }
```

Hanya sync request milik user yang akan diproses. Yang bukan milik user di-skip
diam-diam dari hasil.

**Response (200):**
```json
{
  "data": [
    { "idempotency_key": "dev-A-20260613-001", "status": 201, "data": {...}, "replay": false }
  ]
}
```

#### **Get Sync Status**
```http
GET /api/v1/pos/sync/status/{idempotency_key}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "idempotency_key": "dev-A-20260613-001",
  "status": "DONE",
  "response_status": 201,
  "response_body": { "id": 12, "...": "..." },
  "error_message": null,
  "processed_at": "2026-06-13T12:01:23+07:00"
}
```

#### **POS Transaction Payload (Split Payment)**
Field `payments` menerima array (bukan single object). Contoh penjualan dengan
split cash + member credit:
```json
{
  "client_reference": "SPLIT-001",
  "cooperative_member_id": 42,
  "items": [
    { "pos_product_id": 1, "quantity": 2 }
  ],
  "payments": [
    { "payment_method": "CASH", "amount": 6000, "cash_received": 6000 },
    { "payment_method": "MEMBER_CREDIT", "amount": 7000 }
  ]
}
```
Jumlah seluruh `payments.amount` harus sama dengan `total_amount` (dihitung dari
subtotal - discount). Backend menolak dengan 422 jika selisih.

---

## 🎁 Points & Rewards API

**Base Path:** `/api/v1/points` and `/api/v1/rewards`

For the Kojayaku Rewards page, combine these endpoints with `GET /api/v1/member/reward-redemptions` to show the authenticated member's redemption history.

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
GET /api/v1/points/history?transaction_type=EARNED&start_date=2026-01-01
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
    "per_page": 15,
    "total": 150
  }
}
```

### **List Available Rewards**
```http
GET /api/v1/rewards?category=DISKON&min_points=500
Authorization: Bearer {token}
```

**Query Parameters:**
- `category` (optional): Filter by category
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
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 20
  }
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

## 💸 Loans API (Admin)

**Base Path:** `/api/v1/loans`

### **List Loans**
```http
GET /api/v1/loans?status=APPLIED
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): `APPLIED`, `MANAGER_APPROVED`, `APPROVED`, `ACTIVE`, `PAID_OFF`, `REJECTED`, `DEFAULTED`, `WRITTEN_OFF`
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
    "per_page": 15,
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
  "purpose": "Modal usaha warung"
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
    "status": "APPLIED",
    "estimated_monthly_installment": 888000,
    "created_at": "2026-05-02T10:00:00Z"
  }
}
```

### **Review Loan as Manager**
```http
POST /api/v1/loans/{loan}/review
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Kelayakan usaha dan histori simpanan sudah dicek."
}
```

Requires `review_cooperative_loan` permission. Moves loan from `APPLIED` to `MANAGER_APPROVED`.

### **Final Approve Loan as Pengurus**
```http
POST /api/v1/loans/{loan}/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Disetujui untuk pencairan."
}
```

Requires `approve_cooperative_loan` permission. Moves loan from `MANAGER_APPROVED` to `APPROVED`.

### **Reject Loan**
```http
POST /api/v1/loans/{loan}/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "rejection_reason": "Riwayat angsuran belum memenuhi syarat."
}
```

Requires `review_cooperative_loan` or `approve_cooperative_loan` permission. Rejects loans from `APPLIED` or `MANAGER_APPROVED`.

### **Get Loan Detail**
```http
GET /api/v1/loans/{loan}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
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

### **Loan Calculator (Simulasi Cicilan)**
```http
POST /api/v1/loans/calculator
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

## 👤 Employee Self Service API

**Base Path:** `/api/ess`
**Auth:** Sanctum bearer token
**Abilities:** `ess:read`, `ess:write`, `attendance:read`, `attendance:write`

All ESS endpoints require the authenticated user to have an associated `Employee` record (returns 403 otherwise).

### **Dashboard & Profile**
```http
GET /api/ess/dashboard
GET /api/ess/profile
PUT /api/ess/profile
Authorization: Bearer {token}
```

**Dashboard Response (200):**
```json
{
  "data": {
    "employee": {
      "id": "uuid",
      "employee_code": "EMP001",
      "name": "John Doe",
      "department": { "name": "IT" },
      "position": { "name": "Developer" }
    },
    "today_attendance": {
      "clock_in": "08:00:00",
      "clock_out": null,
      "status": "PRESENT"
    },
    "today_shift": {
      "name": "Pagi",
      "start_time": "08:00:00",
      "end_time": "17:00:00"
    },
    "stats": {
      "attendance_this_month": 20,
      "pending_leaves": 1,
      "approved_leaves_this_year": 5,
      "latest_payroll_period": "2026-04",
      "latest_net_salary": 8500000,
      "expiring_certificates": 2,
      "due_medical_checkups": 1,
      "pending_overtime": 3,
      "pending_reimbursements": 2
    }
  }
}
```

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

**Conflict Response (409):**
```json
{
  "ok": false,
  "error": "Already checked in."
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

Check-out stores `clock_out_latitude`, `clock_out_longitude`, `clock_out_accuracy`, `clock_out_device_id`, and mobile audit data.

**Conflict Response (409):**
```json
{
  "ok": false,
  "error": "Not checked in."
}
```

### **Attendance Correction**
```http
POST /api/ess/attendance/correction
POST /api/ess/attendance/corrections/{attendanceCorrection}/approve
Authorization: Bearer {token}
Content-Type: application/json
```

**Create payload:**
```json
{
  "date": "2026-05-15",
  "corrected_clock_in": "08:05",
  "corrected_clock_out": "17:10",
  "reason": "Lupa check-in dan check-out karena kunjungan lapangan."
}
```

Create returns `PENDING`. Approval writes or updates the related `attendances` row with `status=PRESENT`, links `attendance_corrections.attendance_id`, and stores reviewer audit metadata.

### **THR Entitlement**
```http
GET /api/ess/thr/entitlement?year=2026
Authorization: Bearer {token}
```

Requires `payroll:read`. Returns the authenticated employee's calculated THR entitlement based on cutoff 31 Mei, capped at 12 months of service.

**Response (200):**
```json
{
  "data": {
    "year": 2026,
    "months_worked": 12,
    "base_salary": 6000000,
    "amount": 6000000,
    "status": "DRAFT",
    "calculation_breakdown": {
      "cutoff_date": "2026-05-31",
      "formula": "(base_salary / 12) * months_worked"
    }
  }
}
```

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

Returns roster based on `employee.shift_group` and related `workShift`. Only available if employee has a shift group assigned.

### **Leave Requests**
```http
GET /api/ess/leaves?per_page=15
POST /api/ess/leaves
POST /api/ess/leaves/{leave}/cancel
Authorization: Bearer {token}
```

**Create payload (multipart/form-data):**
```
leave_type_id: 1
start_date: 2026-05-10
end_date: 2026-05-12
reason: Attending family event
attachment: <optional file> (jpg/png/pdf, max 4MB)
```

**List Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "leave_type": {
        "id": 1,
        "name": "Cuti Tahunan"
      },
      "start_date": "2026-05-10",
      "end_date": "2026-05-12",
      "status": "Pending",
      "reason": "Attending family event"
    }
  ],
  "balance": [
    {
      "leave_type_id": 1,
      "name": "Cuti Tahunan",
      "allowance": 12,
      "used": 5,
      "remaining": 7,
      "requires_attachment": false,
      "is_paid": true
    }
  ]
}
```

Cancellation stores `cancel_requested_at`, `cancel_requested_by`, and `cancel_reason`. Only `Pending` leaves can be cancelled (409 otherwise).

### **Overtime**
```http
GET /api/ess/overtime?per_page=15
POST /api/ess/overtime
Authorization: Bearer {token}
Content-Type: application/json
```

**Create payload:**
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

**Create payload:**
```
items[0][category]: TRANSPORT
items[0][description]: Ojek ke kantor
items[0][amount]: 50000
items[0][receipt_date]: 2026-05-06
items[0][receipt_file]: <optional file>
```

**Category options:** `TRANSPORT`, `MEAL`, `MEDICAL`, `LODGING`, `OFFICE_SUPPLIES`, `OTHER`

### **Payslips, Compliance, Notifications**
```http
GET /api/ess/payslips?per_page=12
GET /api/ess/payslips/{payroll}/download
GET /api/ess/compliance
GET /api/ess/notifications?per_page=15
Authorization: Bearer {token}
```

Payslip only returns or downloads payrolls owned by the employee with status `PROCESSED` or `PAID`. Compliance returns certificates and medical checkups owned by the employee. Notifications use Laravel database notifications for the logged-in user.

---

## 🧾 Cooperative SHU Revision

```http
POST /cooperative/shu/{period}/request-revision
Authorization: Session cookie
Content-Type: application/json
```

Requires `manage_cooperative_shu`. Only `CLOSED` or `CLOSED_REVISED` periods can enter `REVISION`; every revision request writes an `approval_logs` audit entry.

**Payload:**
```json
{
  "reason": "Ada koreksi data transaksi anggota setelah tutup buku."
}
```

---

## 🛒 Procurement API

```http
GET /api/v1/procurement/vendors/{vendor}/performance
Authorization: Bearer {token}
```

Requires `reports:read`. Calculates and stores a vendor performance snapshot from purchase orders and goods receive notes, then updates `vendors.rating`.

**Response (200):**
```json
{
  "data": {
    "vendor": {
      "id": "uuid",
      "code": "VND-00001",
      "name": "Vendor A",
      "rating": 5
    },
    "performance": {
      "score": 100,
      "rating": 5,
      "on_time_delivery_rate": 100,
      "quality_acceptance_rate": 100,
      "purchase_order_count": 1,
      "goods_receive_note_count": 1
    }
  }
}
```

---

## 📊 Reports API

**Base Path:** `/api/reports`

### **Certificate Compliance Report**
```http
GET /api/reports/certificate-compliance?department_id=uuid
Authorization: Bearer {token}
```

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

### **Consolidated Reports**
```http
GET /api/reports/consolidated-stats
GET /api/reports/consolidated-attendance
GET /api/reports/consolidated-payroll
GET /api/v1/reports/npl-aging
Authorization: Bearer {token}
```

### **NPL Aging Report**
```http
GET /api/v1/reports/npl-aging?as_of=2026-05-16
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "as_of": "2026-05-16",
    "active_loan_outstanding": 1000000,
    "npl_outstanding": 200000,
    "npl_ratio": 0.2,
    "buckets": [
      {
        "bucket": "91-120",
        "installment_count": 1,
        "outstanding_amount": 200000,
        "provisioning_amount": 100000
      }
    ]
  }
}
```

---

## 🔔 Notifications API

Notifikasi in-app memakai Laravel database notifications sebagai source of truth. Push dan WhatsApp dikirim melalui transactional outbox sebagai channel tambahan. Rencana lengkap event per role ada di `docs/plan_notifikasi.md`.

### **Session/Web Notifications**

**Base Path:** `/api/notifications`

Dipakai oleh ikon notifikasi kanan atas dan halaman `/notifications` untuk role internal maupun anggota yang login via session.

```http
GET /api/notifications?category=loan&status=unread&severity=warning&per_page=15
GET /api/notifications/recent?limit=5
GET /api/notifications/summary
GET /api/notifications/{notification}
PATCH /api/notifications/{notification}/read
POST /api/notifications/mark-all-read
GET /api/notifications/preferences
PUT /api/notifications/preferences
```

**Notification item contract:**
```json
{
  "id": "uuid",
  "type": "database",
  "event_type": "pengurus.loan.final_approval_required",
  "category": "loan",
  "severity": "warning",
  "title": "Final approval pinjaman diperlukan",
  "message": "Pengajuan pinjaman Andi Prasetyo menunggu approval Pengurus Koperasi.",
  "subject": {
    "type": "loan",
    "id": 123,
    "label": "Pinjaman Andi Prasetyo"
  },
  "actor": {
    "id": 45,
    "name": "Manajer Koperasi"
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

**Summary response:**
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

**Preferences update payload:**
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

### **Member Mobile Notifications**

**Base Path:** `/api/v1/member/notifications`

```http
GET /api/v1/member/notifications
GET /api/v1/member/notifications/recent?limit=5
GET /api/v1/member/notifications/unread-count
GET /api/v1/member/notifications/summary
PATCH /api/v1/member/notifications/{notification}/read
POST /api/v1/member/notifications/mark-all-read
GET /api/v1/member/notifications/preferences
PUT /api/v1/member/notifications/preferences
```

All member endpoints require `auth:sanctum` and member token abilities. Member notifications are scoped to the authenticated member user only.

### **Admin/Cooperative Token Notifications**

**Base Path:** `/api/v1/notifications`

```http
GET /api/v1/notifications
GET /api/v1/notifications/recent?limit=5
GET /api/v1/notifications/summary
PATCH /api/v1/notifications/{notification}/read
POST /api/v1/notifications/mark-all-read
GET /api/v1/notifications/preferences
PUT /api/v1/notifications/preferences
```

Authorization:
- Granular cooperative abilities for list, recent, summary, and mutation
  routes. `cooperative:read` and `cooperative:write` are legacy compatibility
  abilities only during the explicitly configured cutover grace phase.
- Results are scoped by authenticated user, organization, role, and permission.

### **Core Cooperative Event Types**

Initial cooperative event categories:
- `member`: onboarding, validation, revision, final approval.
- `dues`: invoice created, due reminder, overdue.
- `payment`: proof uploaded, approval required, approved, rejected.
- `loan`: applied, manager review required, manager reviewed, final approval required, approved, rejected, disbursed, installment due, installment paid, NPL alert.
- `pos`: transaction completed, return processed, void requested, void approved/rejected, shift closed, cash difference.
- `inventory`: low stock, negative stock, stock count review, variance detected.
- `coffee`: order received, brewing, ready, picked up, cancelled.
- `reward`: redemption required, status changed.
- `points`: earned, adjusted, expired.
- `shu`: review required, close required, allocated, revision requested.
- `support`: ticket created, replied, closed.

### **WhatsApp Operational Notifications**

WhatsApp dikirim melalui transactional outbox dengan channel `whatsapp`, sehingga delivery tercatat di `notification_outboxes` dan ikut retry oleh `notifications:outbox:process`.

**Automated flows:**
- `notifications:whatsapp-dues-reminders --days=3` mengantrekan reminder iuran `UNPAID`/`PARTIAL` yang jatuh tempo dalam 3 hari atau sudah lewat jatuh tempo.
- Approval/rejection cuti mengantrekan notifikasi status cuti ke karyawan yang opt-in WhatsApp.

**Environment:**
```
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ENDPOINT=https://graph.facebook.com/v20.0
WHATSAPP_DEFAULT_COUNTRY_CODE=62
```

---

## 📋 Common Response Format

API responses under `/api/*` are normalized by middleware. Legacy payload shapes are preserved for backward compatibility, but every JSON API response includes a `success` boolean.

### **Idempotency for Financial Writes**

Selected mobile/API financial write endpoints accept an `Idempotency-Key` header. Clients should send a stable key when retrying the same create/charge operation after network failure.

Covered examples:
- `POST /api/payments/charge`
- `POST /api/v1/member/savings/withdraw`
- `POST /api/v1/member/payments/proof`
- `POST /api/v1/member/loans`
- `POST /api/v1/member/loans/{loan}/restructure`
- `POST /api/v1/dues/payments`
- `POST /api/v1/loans/apply`
- `POST /api/v1/rewards/{reward}/redeem`
- `POST /api/v1/pos/transactions`
- `POST /api/v1/pos/returns`

Rules:
- Key format: 8-128 characters, letters/numbers plus `:`, `_`, or `-`.
- Reusing the same key with the same payload returns the original response and header `X-Idempotency-Replayed: true`.
- Reusing the same key with a different payload returns `409 CONFLICT`.
- Idempotency records are retained in cache for 24 hours.

### **Response Timing**

API responses include `X-Response-Time-Ms`, and timing metadata is logged with the request id for p95/p99 monitoring.

### **Success: `{ success: true, data?: ..., message?: ... }`**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### **Error: `{ success: false, message, error_code, request_id }`**
```json
{
  "success": false,
  "message": "Resource tidak ditemukan.",
  "errors": {},
  "error": "Resource tidak ditemukan.",
  "error_code": "NOT_FOUND",
  "error_details": {},
  "request_id": "req-01HY..."
}
```

`request_id` matches the `X-Correlation-ID` response header and can be passed by clients using the `X-Correlation-ID` request header.

### **Pagination Response (Laravel Paginator)**
All list endpoints return Laravel's standard paginator format:

Pagination limits are resolved centrally for API, ESS, technician, notification,
cooperative, POS, procurement, certificate, MCU, support, audit, and compliance
list surfaces:

- omitted or empty per_page: 15;
- values below 1 (including 0 and -1): 1;
- values 1 through 50: accepted as requested;
- values above 50: capped at 50;
- non-numeric, array, or malformed values: default to 15;
- the documented cooperative dues management screen may use an explicit
  administrative ceiling of 100.

Controllers must use the shared page-size resolver. They must not parse
per_page or page_size directly from the request.

```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "http://localhost:8000/api/resource?page=1",
  "from": 1,
  "last_page": 7,
  "last_page_url": "http://localhost:8000/api/resource?page=7",
  "links": [...],
  "next_page_url": "http://localhost:8000/api/resource?page=2",
  "path": "http://localhost:8000/api/resource",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 100
}
```

---

## 🧰 Production Operations

Scheduled operational tasks are configured in `routes/console.php`:

- `operations:prune-retention` runs daily at `01:30` and prunes managed rotated log files plus `audit_logs` rows older than `AUDIT_LOG_RETENTION_DAYS`.
- `backup:database --prune` runs daily at `02:30` and writes a DB backup to `BACKUP_DISK` / `BACKUP_DIRECTORY`, then removes backups older than `BACKUP_RETENTION_DAYS`.
- `backup:verify {path?}` verifies a selected backup or the latest backup in `BACKUP_DIRECTORY`; SQLite backups are restored to a temporary read-only database and checked with `PRAGMA integrity_check`.
- `notifications:outbox:process --limit=100` runs every 30 seconds for transactional notification retry.

Environment controls:

```dotenv
LOG_RETENTION_DAYS=30
AUDIT_LOG_RETENTION_DAYS=365
BACKUP_DISK=local
BACKUP_DIRECTORY=backups/database
BACKUP_RETENTION_DAYS=14
```

Manual deployment is available via `.github/workflows/deploy.yml` (`workflow_dispatch`) and executes `bin/deploy.sh` over SSH using `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, and `DEPLOY_SSH_KEY` secrets.

---

## 🔒 Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Insufficient permissions or not assigned |
| 404 | Not Found |
| 409 | Conflict - Already checked in, cannot cancel, etc. |
| 422 | Validation Error |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error |

Business error envelopes may additionally expose `error_code` values such as `PERIOD_LOCKED`, `INSUFFICIENT_BALANCE`, and `BUSINESS_RULE_VIOLATION`.

---

## 🔑 Abilities / Permissions

| Ability | Granted To |
|---------|-----------|
| `*` | Never issued by new token profiles; legacy wildcard tokens require explicit rotation |
| `profile:read` | All authenticated users |
| `member:read`, `member:write` | Anggota, cooperative members |
| `cooperative:read`, `cooperative:write` | Legacy compatibility only during the configured cutover phase; new issuers use granular cooperative abilities |
| `ess:read`, `ess:write` | Employees |
| `attendance:read`, `attendance:write` | Employees |
| `payroll:read` | Employees |
| `work-orders:read` | Technicians |
| `work-orders:write` | Technicians, users with `manage_work_order` |
| `work-orders:review` | Users with `manage_work_order` or `view_work_order_all` |
| `pos:read`, `pos:write` | Pengurus Koperasi, Kasir Koperasi |
| `reports:read` | Pengurus Koperasi, Kasir Koperasi |
| `access_cooperative_pos` | Pengurus Koperasi, Kasir Koperasi |
| `manage_cooperative_member` | Admin koperasi |
| `manage_cooperative_dues` | Admin koperasi |
| `manage_cooperative_payment` | Admin koperasi |
| `view_cooperative_loan` | Admin koperasi & anggota (scoped) |

---

## 📋 Document 05 Response and Pagination Contracts

The generated contract is the authority in docs/openapi.json; run
"php artisan openapi:snapshot --check" to detect drift.

Document 05 API responses use the normalizer's "success: true" field.
Paginated responses contain exactly "data", "links", "meta", and "success".
"meta.per_page" is bounded to 1–50, except the documented administrative dues
invoice endpoint, which may use 1–100.

The following resources are explicit allowlists:

- CooperativeMemberResource for member list/detail responses;
- LoanResource for loan list/detail responses;
- MemberInvoiceResource for /api/v1/dues/invoices;
- CooperativePaymentResource for payment store and approve;
- BatchCooperativePaymentResponse for payment batch results.

Payment resources do not expose "gateway_payload", "proof_path", token data,
authorization headers, encrypted fields, blind indexes, or unrelated model
columns. Invoice fields are "id", "period", "amount", "paid_amount",
"remaining_amount", "due_date", "status", and the nullable
"contribution_type" object. Payment fields are the documented payment identity,
amount, method, status, timestamps, receipt/reference values, and nullable
allowlisted relations.

Standard pagination parameters use "per_page"; /api/notifications/recent
uses "limit" with default 5 and maximum 10. Both are resolved by the same
centralized bounded resolver. Omitted or malformed values use the endpoint
default, values below 1 become 1, and oversized values are clamped.

## 🧪 Testing API

### **Using cURL**

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password","app":"member"}'

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

# 4. Check-in attendance
curl -X POST http://localhost:8000/api/ess/attendance/check-in \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 10.5,
    "device_id": "device-uuid"
  }'
```

---

## 📱 Mobile App Integration Guide

### **Flutter (Dart) Example**

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiClient {
  final String baseUrl = 'http://localhost:8000/api';
  String? _token;

  Future<Map<String, dynamic>> login(String email, String password, {String app = 'member'}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        'app': app,
        'device_name': 'Flutter App',
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      _token = data['token'];
      return data;
    }
    throw Exception('Login failed: ${response.body}');
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? queryParameters}) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: queryParameters);
    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $_token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    throw Exception('Request failed: ${response.body}');
  }

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: {
        'Authorization': 'Bearer $_token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode(body),
    );

    if (response.statusCode == 200 || response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Request failed: ${response.body}');
  }
}

// Usage
final api = ApiClient();
final authResponse = await api.login('user@example.com', 'password', app: 'member');

// Get member dashboard
final dashboard = await api.get('/v1/member/dashboard');

// Get work orders
final workOrders = await api.get('/technician/work-orders', queryParameters: {
  'status': 'OPEN',
  'per_page': '15',
});

// Check-in attendance
final checkIn = await api.post('/ess/attendance/check-in', {
  'latitude': -6.2088,
  'longitude': 106.8456,
  'accuracy': 10.5,
  'device_id': 'device-uuid',
});
```

---

## 🔄 API Changelog

### **API Contract Version 1.0.0**

This is the current API contract metadata version. It does not mean that the
application has published a `v1.0.0` release.

- Authentication with Sanctum tokens (login, logout, session, rotate)
- Technician work orders (CRUD, start, complete, sync with idempotency, attachments, parts, escalation, reopen, timeline)
- Employee Self-Service (dashboard, profile, attendance with geofence, leaves, overtime, reimbursements, payslips, compliance, shift roster, geofence)
- Cooperative members management (CRUD, activate, resign)
- Cooperative dues (invoices, generate, payments, approve)
- Member self-service (dashboard, profile, savings summary/ledger, dues invoices, payments with proof upload, loans, SHU, notifications, support tickets)
- POS (products, transactions)
- Points & rewards (balance, history, list rewards, redeem)
- Loans (list, apply, detail, calculator)
- Notifications (list, read, mark-all, unread count, preferences)
- Reports (certificate compliance, MCU compliance, non-compliant employees, consolidated stats/attendance/payroll)
- Payment gateway integration (charge, webhook with Midtrans compatibility)
- Push token registration for mobile devices
- OpenAPI spec available at `/api/openapi.json`
- Dashboard API (consolidated/organization view)

### **Planned for v1.1.0**
- WhatsApp notifications
- Advanced filtering and sorting
- Export endpoints (Excel, PDF)
- Real-time updates (WebSocket/SSE)

---

*Last Updated: June 10, 2026*
## Saldo Toko Anggota

Endpoint member berikut selalu mengambil anggota aktif yang terhubung dengan
token, lalu membatasi query berdasarkan `organization_id` dan
`cooperative_member_id`. Anggota tidak dapat memilih account milik anggota
lain atau organisasi lain.

- `GET /api/v1/member/store-account/summary`
- `GET /api/v1/member/store-account/ledger?per_page=15&page=1`

Summary mengembalikan `balance` signed dalam Rupiah, `credit_limit`,
`available_spending`, `status`, `status_label`, dan
`balance_label`. `balance_label` bernilai `Saldo tersimpan` untuk
saldo nol/positif dan `Pemakaian/utang toko` untuk saldo negatif.

Setiap item ledger mengembalikan kontrak publik stabil berikut:

```json
{
  "id": 42,
  "entry_type": "pos_purchase",
  "entry_type_label": "Pembelian POS",
  "amount": 75000,
  "effect": "debit",
  "balance_before": 125000,
  "balance_after": 50000,
  "purchaser_name": "Budi Staff",
  "cashier_name": "Siti Kasir",
  "purchase_note": "Diambil untuk tim gudang",
  "transaction_no": "POS-20260720-001",
  "occurred_at": "2026-07-20T14:30:00+07:00",
  "reference_type": "pos_transaction",
  "reference_id": "123",
  "status": "purchase",
  "is_reversed": false
}
```

`reference_type` hanya memakai nilai publik stabil (`pos_transaction`,
`pos_return`, `funding_request`, `store_account`, atau
`ledger_entry`), bukan nama class internal PHP. Nilai internal/legacy yang tidak
dikenal dikembalikan sebagai `null`. `reference_id` selalu string jika tersedia,
atau `null` jika tidak ada. `occurred_at` selalu ISO-8601 dengan timezone. Nama
purchaser dan catatan pada entry pembelian/refund adalah snapshot immutable;
`cashier_name` adalah actor yang mencatat
transaksi. Checkout POS `MEMBER_STORE_ACCOUNT` tidak meminta password atau
PIN; field request yang wajib adalah `purchaser_name` dan
`purchase_note` opsional maksimal 500 karakter.

---

## 🔒 Employee Documents & Sensitive File Storage API (SEC-P0-03)

Employee certificates (SIO K3, training licenses) and medical check-up (MCU) reports are stored on the private filesystem disk `employee_documents` and are strictly inaccessible via `/storage/*`.

### Certificate Document Endpoints

#### 1. Download Certificate Document
```http
GET /api/employees/{employeeId}/certificates/{id}/document
Authorization: Bearer {token}
```
- **Ability Required:** `employee-documents:read`
- **Permissions:** `view_employee_all` or `view_employee_unit` (organization-scoped)
- **Response (200):** Streamed binary file with headers:
  - `Content-Type`: `application/pdf`, `image/jpeg`, or `image/png`
  - `X-Content-Type-Options`: `nosniff`
  - `Cache-Control`: `private, no-store, max-age=0, must-revalidate`
  - `Pragma`: `no-cache`
- **Error Responses:**
  - `401 Unauthorized`: Missing or invalid bearer token.
  - `403 Forbidden`: Token lacks `employee-documents:read` ability.
  - `404 Not Found`: Employee not visible in caller's organization, certificate not found, or employee/certificate mismatch.

#### 2. Upload Certificate Document
```http
POST /api/employees/{employeeId}/certificates/{id}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data
```
- **Ability Required:** `employee-documents:write`
- **Permissions:** `edit_employee` (organization-scoped)
- **Payload:** `document` (file, max 10MB, mime: pdf, jpg, jpeg, png)
- **Response (200):**
```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "document_path": "certificates/{employeeId}/{filename}.pdf",
    "has_document": true,
    "document_download_url": "https://api.kojaya.test/api/employees/{employeeId}/certificates/{id}/document"
  }
}
```

### Medical Check-Up (MCU) Document Endpoints

#### 1. Download MCU Document
```http
GET /api/employees/{employeeId}/mcu/{id}/document
Authorization: Bearer {token}
```
- **Ability Required:** `employee-documents:read`
- **Permissions:** `view_employee_all` or `view_employee_unit` (organization-scoped)
- **Response (200):** Streamed binary file with secure headers (`nosniff`, `no-store`).

#### 2. Upload MCU Document
```http
POST /api/employees/{employeeId}/mcu/{id}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data
```
- **Ability Required:** `employee-documents:write`
- **Permissions:** `edit_employee` (organization-scoped)
- **Payload:** `document` (file, max 10MB, mime: pdf, jpg, jpeg, png)
- **Response (200):**
```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "document_path": "mcu/{employeeId}/{filename}.png",
    "has_document": true,
    "document_download_url": "https://api.kojaya.test/api/employees/{employeeId}/mcu/{id}/document"
  }
}
```

### Resource Serialization Changes
`EmployeeCertificateResource` and `MedicalCheckupResource` no longer return public `/storage/` URLs:
- `document_url`: always `null`
- `has_document`: `boolean` (`true` if file exists)
- `document_download_url`: fully-qualified URL pointing to the authenticated API download endpoint (or `null` if no file attached).

