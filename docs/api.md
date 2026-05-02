# Kojaya ERP - API Documentation

## 📡 API Overview

Kojaya ERP menyediakan **RESTful API** yang lengkap untuk integrasi mobile applications dan third-party systems.

**Base URL:** `http://localhost:8000/api` (development)
**API Version:** v1
**Authentication:** Laravel Sanctum (Token-based)
**Content-Type:** `application/json`

---

## 🔐 Authentication

### **Token Authentication Flow**

#### 1. **Login & Get Token**
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your_password"
}
```

**Response (200):**
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": "uuid",
    "name": "User Name",
    "email": "user@example.com",
    "roles": ["Employee"]
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
POST /api/logout
Authorization: Bearer {token}
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

## 🔧 Technician Work Orders API

**Base Path:** `/api/technician`

### **List Work Orders**
```http
GET /api/technician/work-orders
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): `OPEN`, `IN_PROGRESS`, `COMPLETED`
- `page` (optional): Page number for pagination

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
    "total": 45
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
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "COMPLETED",
    "completed_at": "2026-05-02T17:00:00Z"
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
  "completed": true,
  "notes": "Filter replaced with new one"
}
```

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

## 👤 Employee Self Service API

**Base Path:** `/api/ess` and `/api/employees/{id}`

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
    "id": "uuid",
    "check_in_time": "2026-05-02T08:00:00Z",
    "location": "HQ Office",
    "status": "PRESENT"
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
    "radius": 500,
    "location_name": "HQ Office"
  }
}
```

### **List Leave Requests**
```http
GET /api/leaves/self-service
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "leave_type": "ANNUAL",
      "start_date": "2026-05-10",
      "end_date": "2026-05-12",
      "days": 3,
      "reason": "Family event",
      "status": "PENDING"
    }
  ]
}
```

### **Create Leave Request**
```http
POST /api/leaves/self-service
Authorization: Bearer {token}
Content-Type: application/json

{
  "leave_type_id": "uuid",
  "start_date": "2026-05-10",
  "end_date": "2026-05-12",
  "reason": "Attending family event"
}
```

### **Download Payslip PDF**
```http
GET /api/payrolls/{payroll}/download-pdf
Authorization: Bearer {token}
```

**Response:** PDF file download

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

### **Planned for v1.1.0**
- Payment gateway integration
- WhatsApp notifications
- Advanced filtering and sorting
- Export endpoints (Excel, PDF)
- Real-time updates (WebSocket/SSE)

---

*Last Updated: May 2, 2026*
