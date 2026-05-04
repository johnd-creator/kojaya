# API Reference — Kojayaku Flutter App

**Versi:** 1.0.0
**Base URL:** `{APP_URL}/api`
**Auth:** Bearer token (Sanctum)

---

## 1. Authentication

### 1.1 Login (Fortify)

```
POST /login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

**Success (302 redirect to /dashboard for web, or JSON for API):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "organization_id": "uuid-here",
    "email_verified_at": "2026-01-01T00:00:00.000000Z",
    "two_factor_confirmed_at": null,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

> **Note:** Fortify's default login returns a redirect. For mobile, you may need to create a dedicated API token endpoint or use `POST /login` with `Accept: application/json` header.

### 1.2 Register (Fortify)

```
POST /register
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

### 1.3 Forgot Password (Fortify)

```
POST /forgot-password
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

### 1.4 Reset Password (Fortify)

```
POST /reset-password
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "reset-token-from-email",
  "email": "user@example.com",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### 1.5 Logout

```
POST /logout
Authorization: Bearer {token}
```

### 1.6 Get Current User

```
GET /api/user
Authorization: Bearer {token}
Ability: profile:read
Throttle: 60/min
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "user@example.com",
  "organization_id": "uuid",
  "email_verified_at": "ISO8601",
  "two_factor_confirmed_at": null,
  "created_at": "ISO8601",
  "updated_at": "ISO8601"
}
```

---

## 2. Token Abilities

Sanctum tokens are scoped to abilities. Token expiration: **30 days** (`43,200 minutes`).

| Ability | Description | Used By |
|---------|-------------|---------|
| `profile:read` | Read user profile | `GET /api/user` |
| `cooperative:read` | Read cooperative data | Member/dues/product listing |
| `cooperative:write` | Write cooperative data | Create/update members, dues, payments |
| `pos:read` | Read POS data | Product listing |
| `pos:write` | Write POS data | Create transactions |
| `reports:read` | Read reports | Summary, sales, compliance reports |
| `work-orders:read` | Read work orders | Technician work order listing |
| `work-orders:write` | Write work orders | Start/complete, update checklists |
| `employee-documents:read` | Read employee docs | Certificates, MCU |
| `employee-documents:write` | Write employee docs | Upload/manage certificates, MCU |

---

## 3. Rate Limiting

| Throttle Key | Limit | Applied To |
|-------------|-------|------------|
| `api` | 60 requests/min | All read endpoints |
| `api-write` | 30 requests/min | All write endpoints (POST/PUT/DELETE) |
| Login | 5 requests/min | Per email+IP |

---

## 4. Cooperative Members

### 4.1 List Members

```
GET /api/v1/members
Authorization: Bearer {token}
Ability: cooperative:read
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `search` | string | no | Search by name/email/member_no |
| `status` | string | no | Filter: PENDING, ACTIVE, INACTIVE, RESIGNED |
| `page` | int | no | Page number |
| `per_page` | int | no | Items per page (default 15) |

**Response:** Laravel paginator JSON
```json
{
  "data": [
    {
      "id": 1,
      "organization_id": "uuid",
      "employee_id": null,
      "user_id": 1,
      "member_no": "KOP-001-0001",
      "name": "Ahmad Fauzi",
      "email": "ahmad@example.com",
      "phone": "081234567890",
      "identity_number": "3201234567890001",
      "address": "Jl. Merdeka No. 10",
      "joined_at": "2025-01-15",
      "resigned_at": null,
      "status": "ACTIVE",
      "notes": null,
      "created_at": "ISO8601",
      "updated_at": "ISO8601"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 5, "per_page": 15, "to": 15, "total": 75 }
}
```

### 4.2 Create Member

```
POST /api/v1/members
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Request Body:**
```json
{
  "employee_id": null,
  "user_id": null,
  "name": "Ahmad Fauzi",
  "email": "ahmad@example.com",
  "phone": "081234567890",
  "identity_number": "3201234567890001",
  "address": "Jl. Merdeka No. 10",
  "joined_at": "2025-01-15",
  "status": "ACTIVE",
  "notes": null,
  "member_login_password": "secret123",
  "opening_saving_balance": 500000
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `employee_id` | nullable, exists:employees,id |
| `user_id` | nullable, exists:users,id, unique:cooperative_members,user_id |
| `name` | required, string, max:255 |
| `email` | nullable, email, max:255 |
| `phone` | nullable, string, max:40 |
| `identity_number` | nullable, string, max:40 |
| `address` | nullable, string |
| `joined_at` | nullable, date |
| `status` | nullable, in:PENDING,ACTIVE,INACTIVE,RESIGNED |
| `notes` | nullable, string |
| `member_login_password` | nullable, string, min:8 |
| `opening_saving_balance` | nullable, numeric, min:0 |

**Response (201):**
```json
{
  "data": { /* CooperativeMember object */ }
}
```

### 4.3 Get Member

```
GET /api/v1/members/{member}
Authorization: Bearer {token}
Ability: cooperative:read
```

**Response:** Includes eager-loaded `organization`, `documents`, `invoices.contributionType`, `ledgerEntries`
```json
{
  "data": {
    "id": 1,
    "organization_id": "uuid",
    "member_no": "KOP-001-0001",
    "name": "Ahmad Fauzi",
    "email": "ahmad@example.com",
    "phone": "081234567890",
    "status": "ACTIVE",
    "joined_at": "2025-01-15",
    "resigned_at": null,
    "notes": null,
    "organization": { "id": "uuid", "code": "KOP-001", "name": "Kantor Pusat Koperasi" },
    "documents": [],
    "invoices": [
      {
        "id": 1,
        "cooperative_member_id": 1,
        "cooperative_contribution_type_id": 1,
        "period": "2026-01",
        "amount": "100000.00",
        "paid_amount": "100000.00",
        "due_date": "2026-01-31",
        "status": "PAID",
        "contribution_type": { "id": 1, "code": "SIMWAJIB", "name": "Simpanan Wajib", "category": "SAVINGS", "default_amount": "100000.00", "frequency": "MONTHLY", "is_active": true }
      }
    ],
    "ledger_entries": [
      { "id": 1, "entry_type": "CREDIT", "debit": "0.00", "credit": "100000.00", "description": "Simpanan Wajib Jan 2026", "posted_at": "2026-01-31" }
    ]
  }
}
```

### 4.4 Update Member

```
PUT /api/v1/members/{member}
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

Same fields as Create. `status` is **required** (not nullable). `user_id` ignores current member.

### 4.5 Activate Member

```
POST /api/v1/members/{member}/activate
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Response:**
```json
{
  "data": { /* CooperativeMember with status=ACTIVE */ }
}
```

### 4.6 Resign Member

```
POST /api/v1/members/{member}/resign
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Response:**
```json
{
  "data": { /* CooperativeMember with status=RESIGNED, resigned_at=today */ }
}
```

---

## 5. Cooperative Dues

### 5.1 List Dues Invoices

```
GET /api/v1/dues/invoices
Authorization: Bearer {token}
Ability: cooperative:read
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `member_id` | int | no | Filter by member (Anggota only sees own) |
| `status` | string | no | Filter: UNPAID, PARTIAL, PAID |
| `period` | string | no | Filter: Y-m format |
| `page` | int | no | Page number |
| `per_page` | int | no | Items per page |

**Response:** Paginated list of `CooperativeDuesInvoice` with `member` and `contributionType` eager-loaded.

### 5.2 Generate Dues

```
POST /api/v1/dues/generate
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Request Body:**
```json
{
  "period": "2026-05"
}
```

**Validation:** `period` required, date_format:Y-m

**Response (201):**
```json
{
  "created": 45
}
```

---

## 6. Cooperative Payments

### 6.1 Record Payment

```
POST /api/v1/dues/payments
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Request Body:**
```json
{
  "cooperative_member_id": 1,
  "cooperative_dues_invoice_id": 5,
  "amount": 100000,
  "payment_method": "CASH",
  "paid_at": "2026-05-01",
  "reference_no": null,
  "notes": null,
  "status": "APPROVED"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `cooperative_member_id` | required, exists:cooperative_members,id |
| `cooperative_dues_invoice_id` | nullable, exists:cooperative_dues_invoices,id |
| `amount` | required, numeric, min:1 |
| `payment_method` | required, in:CASH,TRANSFER,QRIS |
| `paid_at` | required, date |
| `reference_no` | nullable, string, max:255 |
| `notes` | nullable, string |
| `status` | nullable, in:PENDING,APPROVED |

**Response (201):**
```json
{
  "data": { /* CooperativePayment object */ }
}
```

### 6.2 Approve Payment

```
POST /api/v1/dues/payments/{payment}/approve
Authorization: Bearer {token}
Abilities: cooperative:write
Throttle: 30/min
```

**Response:**
```json
{
  "data": { /* CooperativePayment with status=APPROVED, approved_at=now */ }
}
```

---

## 7. POS (Point of Sale)

### 7.1 List Products

```
GET /api/v1/pos/products
Authorization: Bearer {token}
Ability: pos:read
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `search` | string | no | Search by name/sku/barcode |
| `category_id` | int | no | Filter by category |
| `is_active` | bool | no | Default: true |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "pos_category_id": 1,
      "sku": "SKU-001",
      "barcode": "8991234567890",
      "name": "Air Mineral 600ml",
      "cost_price": "2000.00",
      "sale_price": "3500.00",
      "stock": 100,
      "minimum_stock": 10,
      "is_active": true,
      "category": {
        "id": 1,
        "name": "Minuman",
        "slug": "minuman",
        "is_active": true
      }
    }
  ]
}
```

### 7.2 Create Transaction

```
POST /api/v1/pos/transactions
Authorization: Bearer {token}
Abilities: pos:write
Throttle: 30/min
```

**Request Body:**
```json
{
  "client_reference": "mobile-uuid-123",
  "cooperative_member_id": null,
  "payment_method": "CASH",
  "reference_no": null,
  "discount_amount": 0,
  "items": [
    { "pos_product_id": 1, "quantity": 2 },
    { "pos_product_id": 5, "quantity": 1 }
  ]
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `client_reference` | nullable, string, max:80 |
| `cooperative_member_id` | nullable, exists:cooperative_members,id |
| `payment_method` | required, in:CASH,TRANSFER,QRIS,MEMBER_CREDIT |
| `reference_no` | nullable, string, max:255 |
| `discount_amount` | nullable, numeric, min:0 |
| `items` | required, array, min:1 |
| `items.*.pos_product_id` | required, exists:pos_products,id |
| `items.*.quantity` | required, integer, min:1 |

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "transaction_no": "POS-20260501-0001",
    "client_reference": "mobile-uuid-123",
    "cooperative_member_id": null,
    "cashier_id": 1,
    "subtotal": "10500.00",
    "discount_amount": "0.00",
    "total_amount": "10500.00",
    "gross_profit": "4500.00",
    "status": "COMPLETED",
    "sold_at": "2026-05-01T10:30:00.000000Z",
    "items": [
      {
        "id": 1,
        "pos_transaction_id": 1,
        "pos_product_id": 1,
        "quantity": 2,
        "unit_price": "3500.00",
        "cost_price": "2000.00",
        "unit_profit": "1500.00",
        "line_total": "7000.00",
        "line_profit": "3000.00"
      }
    ]
  }
}
```

---

## 8. Reports

### 8.1 Cooperative Summary

```
GET /api/v1/reports/cooperative-summary
Authorization: Bearer {token}
Ability: reports:read
```

**Response:**
```json
{
  "data": {
    "active_members": 75,
    "saving_balance": 37500000.00,
    "member_credit_balance": 5000000.00,
    "unpaid_dues": 2500000.00,
    "today_sales": 1500000.00,
    "monthly_sales": 35000000.00,
    "low_stock_products": 3,
    "annual_pos_profit": 12000000.00,
    "annual_pos_points": 4500,
    "latest_shu_year": 2025,
    "latest_shu_total": 50000000.00
  }
}
```

### 8.2 Sales Report

```
GET /api/v1/reports/sales
Authorization: Bearer {token}
Ability: reports:read
```

**Response:**
```json
{
  "data": [
    { "date": "2026-05-01", "transactions": 15, "total": 1500000.00 },
    { "date": "2026-04-30", "transactions": 12, "total": 1200000.00 }
  ]
}
```

### 8.3 Certificate Compliance

```
GET /api/reports/certificate-compliance
Authorization: Bearer {token}
Ability: reports:read
```

**Response:**
```json
{
  "summary": {
    "total": 50,
    "valid": 40,
    "expiring": 5,
    "expired": 5,
    "compliance_rate": 80.0
  },
  "expiring_soon": [ /* EmployeeCertificateResource[] */ ]
}
```

### 8.4 MCU Compliance

```
GET /api/reports/mcu-compliance
Authorization: Bearer {token}
Ability: reports:read
```

**Response:**
```json
{
  "summary": {
    "total": 50,
    "up_to_date": 42,
    "due": 5,
    "overdue": 3,
    "compliance_rate": 84.0
  },
  "due_soon": [ /* MedicalCheckupResource[] */ ]
}
```

### 8.5 Non-Compliant Employees

```
GET /api/reports/non-compliant-employees
Authorization: Bearer {token}
Ability: reports:read
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "Ahmad",
      "last_name": "Fauzi",
      "employee_code": "EMP-001",
      "valid_certificates": 2,
      "next_mcu_date": "2026-03-01"
    }
  ]
}
```

---

## 9. Technician Work Orders

### 9.1 List Work Orders

```
GET /api/technician/work-orders
Authorization: Bearer {token}
Ability: work-orders:read
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "asset_id": "uuid",
      "organization_id": "uuid",
      "type": "PREVENTIVE",
      "priority": "MEDIUM",
      "status": "OPEN",
      "description": "Monthly inspection",
      "assigned_to": 1,
      "completed_at": null,
      "asset": { "id": "uuid", "code": "AST-001", "name": "Generator Set A" },
      "organization": { "id": "uuid", "name": "Site Jakarta" },
      "checklists": [],
      "parts": []
    }
  ]
}
```

### 9.2 Get Work Order

```
GET /api/technician/work-orders/{id}
Authorization: Bearer {token}
Ability: work-orders:read
```

Includes eager-loaded `asset`, `organization`, `checklists`, `parts.sparePart`.

### 9.3 Start Work Order

```
POST /api/technician/work-orders/{id}/start
Authorization: Bearer {token}
Abilities: work-orders:write
Throttle: 30/min
```

**Response:**
```json
{
  "success": true,
  "message": "Work order started",
  "data": { /* WorkOrder with status=IN_PROGRESS */ }
}
```

### 9.4 Complete Work Order

```
POST /api/technician/work-orders/{id}/complete
Authorization: Bearer {token}
Abilities: work-orders:write
Throttle: 30/min
```

**Error (422) if pending checklists:**
```json
{
  "success": false,
  "message": "Cannot complete. 3 checklist items are pending."
}
```

### 9.5 Update Checklist Item

```
POST /api/technician/work-orders/{id}/checklists/{checklistId}
Authorization: Bearer {token}
Abilities: work-orders:write
Throttle: 30/min
```

**Request Body:**
```json
{
  "is_checked": true,
  "notes": "Inspected, all good"
}
```

**Validation:** `is_checked` required boolean, `notes` nullable string.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "work_order_id": "uuid",
    "item_name": "Check oil level",
    "item_description": "Verify oil level is between min-max",
    "is_checked": true,
    "notes": "Inspected, all good",
    "checked_by": 1,
    "checked_at": "2026-05-01T10:00:00.000000Z"
  }
}
```

---

## 10. Employee Certificates

### 10.1 List Certificates

```
GET /api/employees/{employeeId}/certificates
Authorization: Bearer {token}
Ability: employee-documents:read
```

**Response:** Paginated `EmployeeCertificateResource` collection
```json
{
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "certificate_type": "SIO_K3",
      "certificate_type_label": "SIO K3",
      "certificate_number": "SIO-2025-001",
      "issue_date": "2025-01-15",
      "expiry_date": "2026-01-15",
      "issuing_authority": "Kemenaker",
      "document_path": "certificates/sio-2025-001.pdf",
      "document_url": "https://storage.example.com/certificates/sio-2025-001.pdf",
      "status": "VALID",
      "status_label": "Valid",
      "notes": null,
      "is_expiring": false,
      "is_expired": false,
      "days_until_expiry": 259,
      "created_at": "ISO8601",
      "updated_at": "ISO8601"
    }
  ]
}
```

### 10.2 Create Certificate

```
POST /api/employees/{employeeId}/certificates
Authorization: Bearer {token}
Abilities: employee-documents:write
Throttle: 30/min
```

**Request Body (multipart/form-data):**
| Field | Rules |
|-------|-------|
| `certificate_type` | required, in:SIO_K3,TRAINING,OTHER |
| `certificate_number` | required, string, max:255 |
| `issue_date` | required, date |
| `expiry_date` | nullable, date, after:issue_date |
| `issuing_authority` | nullable, string, max:255 |
| `status` | sometimes, in:VALID,EXPIRED,REVOKED |
| `notes` | nullable, string |

### 10.3 Update Certificate

```
PUT /api/employees/{employeeId}/certificates/{id}
Authorization: Bearer {token}
Abilities: employee-documents:write
Throttle: 30/min
```

Same fields as create, but all use `sometimes` instead of `required`.

### 10.4 Delete Certificate

```
DELETE /api/employees/{employeeId}/certificates/{id}
Authorization: Bearer {token}
Abilities: employee-documents:write
Throttle: 30/min
```

**Response:**
```json
{
  "success": true,
  "message": "Certificate deleted successfully"
}
```

### 10.5 Upload Certificate Document

```
POST /api/employees/{employeeId}/certificates/{id}/upload
Authorization: Bearer {token}
Abilities: employee-documents:write
Content-Type: multipart/form-data
Throttle: 30/min
```

**Request Body:**
| Field | Rules |
|-------|-------|
| `document` | required, file, mimes:pdf,jpg,jpeg,png, max:2048 |

**Response:**
```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "document_path": "certificates/abc.pdf",
    "document_url": "https://storage.example.com/certificates/abc.pdf"
  }
}
```

---

## 11. Medical Checkups (MCU)

### 11.1 List MCU Records

```
GET /api/employees/{employeeId}/mcu
Authorization: Bearer {token}
Ability: employee-documents:read
```

**Response:** Paginated `MedicalCheckupResource` collection
```json
{
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "checkup_date": "2025-06-15",
      "next_checkup_date": "2026-06-15",
      "result": "FIT",
      "result_label": "Fit",
      "result_color": "green",
      "fit_to_work": true,
      "notes": null,
      "document_path": "mcu/report-2025.pdf",
      "document_url": "https://storage.example.com/mcu/report-2025.pdf",
      "doctor_name": "Dr. Siti",
      "clinic_name": "Klinik Sehat",
      "is_due": false,
      "is_overdue": false,
      "days_until_due": 414,
      "created_at": "ISO8601",
      "updated_at": "ISO8601"
    }
  ]
}
```

### 11.2 Create MCU

```
POST /api/employees/{employeeId}/mcu
Authorization: Bearer {token}
Abilities: employee-documents:write
Throttle: 30/min
```

**Request Body:**
| Field | Rules |
|-------|-------|
| `checkup_date` | required, date |
| `next_checkup_date` | nullable, date, after:checkup_date |
| `result` | required, in:FIT,FIT_WITH_RESTRICTION,UNFIT |
| `fit_to_work` | sometimes, boolean |
| `notes` | nullable, string |
| `doctor_name` | nullable, string, max:255 |
| `clinic_name` | nullable, string, max:255 |

### 11.3 Upload MCU Document

```
POST /api/employees/{employeeId}/mcu/{id}/upload
Authorization: Bearer {token}
Abilities: employee-documents:write
Content-Type: multipart/form-data
Throttle: 30/min
```

Same as certificate upload (file: `document`, mimes: pdf/jpg/jpeg/png, max: 2048KB).

---

## 12. Audit Logs (Session Auth Only)

> **Note:** Audit logs use `auth:web` (session-based). These endpoints are NOT available via Sanctum tokens. They are listed here for reference only.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/audit-logs` | Paginated logs with filters (user_id, module, action, date range) |
| GET | `/api/audit-logs/{id}` | Single log entry |
| GET | `/api/audit-logs/history/{type}/{id}` | Audit history for a subject |
| GET | `/api/audit-logs/export` | Export filtered logs |

---

## 13. Common Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 422 Validation Error
```json
{
  "message": "The amount field is required. (and 1 more error)",
  "errors": {
    "amount": ["The amount field is required."],
    "payment_method": ["The selected payment method is invalid."]
  }
}
```

### 429 Rate Limited
```json
{
  "message": "Too Many Attempts."
}
```

### 500 Server Error
```json
{
  "message": "Server Error"
}
```

---

## 14. Response Format Convention

| Endpoint Type | Response Key | Pattern |
|--------------|-------------|---------|
| API V1 controllers | `{ "data": ... }` | Direct model serialization |
| Technician controller | `{ "success": bool, "data": ... }` | Custom wrapper |
| Technician errors | `{ "success": false, "message": "..." }` | Error wrapper |
| Compliance reports | `{ "summary": {...}, "expiring_soon": [...] }` | Summary + details |
| Resources | `{ "data": ResourceObject }` or `{ "data": [ResourceObject] }` | Eloquent API Resource |
| Paginated | Standard Laravel paginator with `data`, `links`, `meta` | — |

---

*Dokumen ini harus diperbarui setiap ada perubahan endpoint API.*
