# Feature Modules — Kojayaku Flutter App

**Versi:** 1.0.0
**Purpose:** Per-module development guide mapping Laravel backend features to Flutter screens and logic.

---

## Module Overview

| Module | Priority | Primary Users | API Prefix |
|--------|----------|---------------|------------|
| Auth | P0 | All | `/login`, `/api/user` |
| Cooperative Dashboard | P0 | Anggota | `/api/v1/reports/cooperative-summary` |
| Cooperative Members | P0 | Pengurus, Kasir | `/api/v1/members` |
| Cooperative Dues | P0 | Pengurus, Kasir | `/api/v1/dues` |
| Cooperative Payments | P0 | Pengurus, Kasir | `/api/v1/dues/payments` |
| POS Transactions | P0 | Kasir | `/api/v1/pos` |
| Technician Work Orders | P1 | Teknisi | `/api/technician` |
| Employee Certificates | P1 | Teknisi, HR | `/api/employees/{id}/certificates` |
| Medical Checkups | P1 | Teknisi, HR | `/api/employees/{id}/mcu` |
| Compliance Reports | P2 | Management | `/api/reports` |
| Cooperative Reports | P1 | Pengurus | `/api/v1/reports` |

---

## 1. Auth Module

### Screens
- `LoginScreen` — Email + password form
- `ForgotPasswordScreen` — Email input
- `ResetPasswordScreen` — New password form (deep-linked)
- `SplashScreen` — Token validation + role routing

### Data Flow
```
LoginScreen → AuthNotifier.login(email, password)
    → AuthRepository.login()
        → POST /login { email, password, abilities }
        → Store token in SecureStorage
        → GET /api/user → cache UserModel
    → GoRouter redirect → /home
```

### Key Files
```
features/auth/
├── data/
│   ├── datasources/auth_remote_datasource.dart
│   ├── models/                          # UserModel already in core
│   └── repositories/auth_repository_impl.dart
├── domain/
│   ├── repositories/auth_repository.dart    # Abstract
│   └── usecases/
│       ├── login_usecase.dart
│       ├── logout_usecase.dart
│       └── validate_token_usecase.dart
└── presentation/
    ├── screens/login_screen.dart
    ├── providers/auth_provider.dart
    └── widgets/login_form.dart
```

---

## 2. Cooperative Dashboard Module

### Screens
- `CooperativeDashboardScreen` — Summary cards + quick actions

### API Endpoints
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/reports/cooperative-summary` | Dashboard stats |
| GET | `/api/v1/reports/sales` | Sales chart data |

### Dashboard Data Model
```dart
@freezed
class CooperativeSummary with _$CooperativeSummary {
  const factory CooperativeSummary({
    required int activeMembers,
    required double savingBalance,
    required double memberCreditBalance,
    required double unpaidDues,
    required double todaySales,
    required double monthlySales,
    required int lowStockProducts,
    required double annualPosProfit,
    required int annualPosPoints,
    int? latestShuYear,
    double? latestShuTotal,
  }) = _CooperativeSummary;

  factory CooperativeSummary.fromJson(Map<String, dynamic> json) =>
      _$CooperativeSummaryFromJson(json);
}
```

### UI Layout
```
┌─────────────────────────────────┐
│ Dashboard Koperasi              │
├─────────────────────────────────┤
│ ┌───────────┐ ┌───────────┐   │
│ │ Anggota   │ │ Simpanan  │   │
│ │ Aktif: 75 │ │ Rp 37.5M  │   │
│ └───────────┘ └───────────┘   │
│ ┌───────────┐ ┌───────────┐   │
│ │ Piutang   │ │ Iuran     │   │
│ │ Rp 5M     │ │ Rp 2.5M   │   │
│ └───────────┘ └───────────┘   │
├─────────────────────────────────┤
│ Penjualan Hari Ini              │
│ Rp 1,500,000 (15 transaksi)    │
│ ████████████░░░░░░░░           │
├─────────────────────────────────┤
│ Stok Rendah: 3 produk          │
│ [Lihat Detail →]               │
├─────────────────────────────────┤
│ SHU Terakhir (2025)             │
│ Rp 50,000,000                  │
└─────────────────────────────────┘
```

---

## 3. Cooperative Members Module

### Screens
- `MemberListScreen` — Paginated list with search/filter
- `MemberDetailScreen` — Full member info + invoices + ledger
- `MemberFormScreen` — Create/Edit member

### API Endpoints
| Method | Endpoint | Ability | Purpose |
|--------|----------|---------|---------|
| GET | `/api/v1/members` | cooperative:read | List members |
| POST | `/api/v1/members` | cooperative:write | Create member |
| GET | `/api/v1/members/{id}` | cooperative:read | Get detail |
| PUT | `/api/v1/members/{id}` | cooperative:write | Update member |
| POST | `/api/v1/members/{id}/activate` | cooperative:write | Activate |
| POST | `/api/v1/members/{id}/resign` | cooperative:write | Resign |

### Key Provider
```dart
@riverpod
class MemberList extends _$MemberList {
  int _page = 1;
  String? _search;
  String? _status;

  @override
  FutureOr<List<CooperativeMemberModel>> build() async {
    return _fetchMembers();
  }

  Future<List<CooperativeMemberModel>> _fetchMembers() async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/v1/members',
      queryParameters: {
        'page': _page,
        'search': _search,
        'status': _status,
      },
    );
    final list = (response.data['data'] as List)
        .map((e) => CooperativeMemberModel.fromJson(e))
        .toList();
    return list;
  }

  Future<void> search(String query) async {
    _search = query.isEmpty ? null : query;
    _page = 1;
    ref.invalidateSelf();
  }

  Future<void> loadMore() async {
    _page++;
    final more = await _fetchMembers();
    state = AsyncData([...state.valueOrNull ?? [], ...more]);
  }
}
```

### Member Detail Tabs
1. **Info** — Name, email, phone, status, joined date
2. **Iuran** — Dues invoices list (paginated)
3. **Pembayaran** — Payment history
4. **Buku Besar** — Ledger entries (debit/credit)

---

## 4. Cooperative Dues Module

### Screens
- `DuesInvoiceListScreen` — Paginated invoice list with filters
- `DuesGenerateScreen` — Period picker, generate button

### API Endpoints
| Method | Endpoint | Ability | Purpose |
|--------|----------|---------|---------|
| GET | `/api/v1/dues/invoices` | cooperative:read | List invoices |
| POST | `/api/v1/dues/generate` | cooperative:write | Generate period invoices |

### Invoice Status Flow
```
UNPAID → (partial payment) → PARTIAL → (full payment) → PAID
```

### Validation (Generate)
- `period`: required, `Y-m` format (e.g., `2026-05`)

---

## 5. Cooperative Payments Module

### Screens
- `PaymentListScreen` — Payment history with filters
- `PaymentFormScreen` — Record new payment
- `PaymentApprovalScreen` — Approve pending payments

### API Endpoints
| Method | Endpoint | Ability | Purpose |
|--------|----------|---------|---------|
| POST | `/api/v1/dues/payments` | cooperative:write | Record payment |
| POST | `/api/v1/dues/payments/{id}/approve` | cooperative:write | Approve payment |

### Payment Form Fields
| Field | Type | Required | Options |
|-------|------|----------|---------|
| `cooperative_member_id` | int | Yes | Member dropdown |
| `cooperative_dues_invoice_id` | int | No | Invoice dropdown (filtered by member) |
| `amount` | double | Yes | Min: 1 |
| `payment_method` | string | Yes | CASH, TRANSFER, QRIS |
| `paid_at` | date | Yes | Date picker |
| `reference_no` | string | No | Max 255 |
| `notes` | string | No | Multi-line |
| `status` | string | No | PENDING, APPROVED (auto-approve for cashiers) |

---

## 6. POS Transaction Module

### Screens
- `PosProductScreen` — Product grid/list with search
- `PosCartScreen` — Cart with quantity controls
- `PosCheckoutScreen` — Payment method + confirm
- `PosReceiptScreen` — Transaction summary

### API Endpoints
| Method | Endpoint | Ability | Purpose |
|--------|----------|---------|---------|
| GET | `/api/v1/pos/products` | pos:read | List products |
| POST | `/api/v1/pos/transactions` | pos:write | Create transaction |

### POS Flow
```
Product List → Add to Cart → Checkout → Select Payment → Confirm → Receipt
     │              │            │            │               │
     │              │            │            │               ▼
     │              │            │            │      POST /api/v1/pos/transactions
     │              │            │            │               │
     ▼              ▼            ▼            ▼               ▼
  GET products   local state  cart summary  payment dialog  receipt view
```

### Cart State (Local)
```dart
@riverpod
class PosCart extends _$PosCart {
  @override
  List<CartItem> build() => [];

  void addItem(PosProductModel product, int quantity) {
    final index = state.indexWhere((i) => i.product.id == product.id);
    if (index >= 0) {
      state = [
        ...state..removeAt(index),
        state[index].copyWith(quantity: state[index].quantity + quantity),
      ];
    } else {
      state = [...state, CartItem(product: product, quantity: quantity)];
    }
  }

  double get total => state.fold(0, (sum, item) => sum + item.lineTotal);

  void clear() => state = [];
}

@freezed
class CartItem with _$CartItem {
  const factory CartItem({
    required PosProductModel product,
    required int quantity,
  }) = _CartItem;

  const CartItem._();

  double get lineTotal => product.salePrice * quantity;
}
```

### Transaction Submission
```dart
Future<PosTransactionModel> submitTransaction({
  required List<CartItem> items,
  required String paymentMethod,
  int? memberId,
  double? discountAmount,
}) async {
  final response = await _dio.post(
    '/api/v1/pos/transactions',
    data: {
      'payment_method': paymentMethod,
      if (memberId != null) 'cooperative_member_id': memberId,
      if (discountAmount != null) 'discount_amount': discountAmount,
      'items': items
          .map((i) => {
                'pos_product_id': i.product.id,
                'quantity': i.quantity,
              })
          .toList(),
    },
  );
  return PosTransactionModel.fromJson(response.data['data']);
}
```

---

## 7. Technician Work Orders Module

### Screens
- `WorkOrderListScreen` — Assigned work orders
- `WorkOrderDetailScreen` — Details + checklists + parts
- `ChecklistItemScreen` — Check/uncheck + notes

### API Endpoints
| Method | Endpoint | Ability | Purpose |
|--------|----------|---------|---------|
| GET | `/api/technician/work-orders` | work-orders:read | List assigned |
| GET | `/api/technician/work-orders/{id}` | work-orders:read | Get detail |
| POST | `/api/technician/work-orders/{id}/start` | work-orders:write | Start WO |
| POST | `/api/technician/work-orders/{id}/complete` | work-orders:write | Complete WO |
| POST | `/api/technician/work-orders/{id}/checklists/{cid}` | work-orders:write | Update checklist |

### Work Order Status Flow
```
OPEN → (start) → IN_PROGRESS → (complete all checklists) → COMPLETED
```

### Key Business Rules
- Only work orders assigned to the authenticated user are visible
- Cannot complete if any checklist items are pending
- `is_checked` must be set to `true` for each checklist before completing
- Completing auto-deducts spare part stock via `deductPartsStock()`

### Provider Pattern
```dart
@riverpod
class WorkOrderDetail extends _$WorkOrderDetail {
  @override
  FutureOr<WorkOrderModel> build(String id) async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/technician/work-orders/$id',
    );
    return WorkOrderModel.fromJson(response.data['data']);
  }

  Future<void> start() async {
    await ref.read(apiClientProvider).dio.post(
      '/api/technician/work-orders/${state.value!.id}/start',
    );
    ref.invalidateSelf();
  }

  Future<void> updateChecklist(String checklistId, bool isChecked, String? notes) async {
    await ref.read(apiClientProvider).dio.post(
      '/api/technician/work-orders/${state.value!.id}/checklists/$checklistId',
      data: {'is_checked': isChecked, 'notes': notes},
    );
    ref.invalidateSelf();
  }

  Future<void> complete() async {
    await ref.read(apiClientProvider).dio.post(
      '/api/technician/work-orders/${state.value!.id}/complete',
    );
    ref.invalidateSelf();
  }
}
```

---

## 8. Employee Certificates Module

### Screens
- `CertificateListScreen` — List with status badges
- `CertificateFormScreen` — Create/Edit certificate
- `CertificateUploadScreen` — Upload document (PDF/image)

### API Endpoints
| Method | Endpoint | Ability |
|--------|----------|---------|
| GET | `/api/employees/{employeeId}/certificates` | employee-documents:read |
| POST | `/api/employees/{employeeId}/certificates` | employee-documents:write |
| GET | `/api/employees/{employeeId}/certificates/{id}` | employee-documents:read |
| PUT | `/api/employees/{employeeId}/certificates/{id}` | employee-documents:write |
| DELETE | `/api/employees/{employeeId}/certificates/{id}` | employee-documents:write |
| POST | `/api/employees/{employeeId}/certificates/{id}/upload` | employee-documents:write |

### Upload Implementation
```dart
Future<void> uploadDocument({
  required int employeeId,
  required int certificateId,
  required File file,
}) async {
  final formData = FormData.fromMap({
    'document': await MultipartFile.fromFile(
      file.path,
      filename: file.path.split('/').last,
    ),
  });

  await _dio.post(
    '/api/employees/$employeeId/certificates/$certificateId/upload',
    data: formData,
    options: Options(contentType: 'multipart/form-data'),
  );
}
```

### Computed Fields (from EmployeeCertificateResource)
The API response includes these computed fields that are NOT on the model:
- `certificate_type_label` — Human-readable type
- `status_label` — Human-readable status
- `is_expiring` — Within 60 days of expiry
- `is_expired` — Past expiry date
- `days_until_expiry` — Int or null
- `document_url` — Full storage URL

---

## 9. Medical Checkups Module

### Screens
- `McuListScreen` — MCU history with status
- `McuFormScreen` — Create/Edit MCU record
- `McuUploadScreen` — Upload MCU document

### API Endpoints
| Method | Endpoint | Ability |
|--------|----------|---------|
| GET | `/api/employees/{employeeId}/mcu` | employee-documents:read |
| POST | `/api/employees/{employeeId}/mcu` | employee-documents:write |
| GET | `/api/employees/{employeeId}/mcu/{id}` | employee-documents:read |
| PUT | `/api/employees/{employeeId}/mcu/{id}` | employee-documents:write |
| DELETE | `/api/employees/{employeeId}/mcu/{id}` | employee-documents:write |
| POST | `/api/employees/{employeeId}/mcu/{id}/upload` | employee-documents:write |

### Computed Fields (from MedicalCheckupResource)
- `result_label` — Fit / Fit with Restriction / Unfit
- `result_color` — green / yellow / red
- `is_due` — Within 30 days of next checkup
- `is_overdue` — Past next checkup date
- `days_until_due` — Int or null
- `document_url` — Full storage URL

### Business Rules
- `next_checkup_date` is auto-calculated as `checkup_date + 1 year` if not provided
- `fit_to_work` is auto-set from `result.isFitForWork()` if not provided
- Status auto-updates: if `expiry_date < now`, certificate auto-set to EXPIRED

---

## 10. Compliance Reports Module

### Screens
- `ComplianceDashboardScreen` — Certificate + MCU compliance rates
- `NonCompliantEmployeesScreen` — Employees needing attention

### API Endpoints
| Method | Endpoint | Ability |
|--------|----------|---------|
| GET | `/api/reports/certificate-compliance` | reports:read |
| GET | `/api/reports/mcu-compliance` | reports:read |
| GET | `/api/reports/non-compliant-employees` | reports:read |

### UI Cards
```
┌─────────────────────────────────┐
│ Kepatuhan Sertifikasi           │
│ ┌─────────────────────────────┐ │
│ │ ██████████████░░░░ 80%     │ │
│ │ 40 valid / 5 expiring / 5 expired │
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│ Kepatuhan MCU                   │
│ ┌─────────────────────────────┐ │
│ │ ████████████████░░ 84%     │ │
│ │ 42 ok / 5 due / 3 overdue  │
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│ Pegawai Tidak Patuh: 8         │
│ [Lihat Detail →]               │
└─────────────────────────────────┘
```

---

## 11. Cross-Module: Error Handling

All modules should handle these common error scenarios:

| Error | Action |
|-------|--------|
| 401 Unauthorized | Redirect to login, clear token |
| 403 Forbidden | Show "Akses ditolak" message |
| 422 Validation | Display field errors in form |
| 429 Rate Limited | Show "Terlalu banyak permintaan" |
| Timeout | Show "Koneksi timeout" with retry |
| No connection | Show offline banner, queue if applicable |

```dart
Future<void> handleApiError(DioException e, WidgetRef ref) async {
  switch (e.response?.statusCode) {
    case 401:
      await ref.read(authNotifierProvider.notifier).logout();
      // GoRouter will redirect to login
    case 403:
      ScaffoldMessenger.showSnackBar('Akses ditolak');
    case 422:
      final errors = e.response?.data['errors'] as Map?;
      // Display field errors on form
    case 429:
      ScaffoldMessenger.showSnackBar('Terlalu banyak permintaan. Coba lagi nanti.');
    default:
      ScaffoldMessenger.showSnackBar('Terjadi kesalahan. Coba lagi.');
  }
}
```

---

*Dokumen ini harus diperbarui setiap ada penambahan modul baru.*
