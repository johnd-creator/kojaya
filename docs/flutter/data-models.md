# Data Models — Laravel to Dart Mapping

**Versi:** 1.0.0
**Purpose:** Map every Laravel Eloquent model to Dart data classes for the Flutter app.

---

## 1. Conventions

### Laravel → Dart Type Mapping

| Laravel Cast | Dart Type | Freezed Type |
|-------------|-----------|--------------|
| `string` | `String` | `String` |
| `integer` | `int` | `int` |
| `boolean` | `bool` | `bool` |
| `decimal:2` | `double` | `double` |
| `float` | `double` | `double` |
| `date` | `DateTime` | `DateTime` |
| `datetime` | `DateTime` | `DateTime` |
| `array` / `json` | `List<dynamic>` / `Map<String, dynamic>` | `List<dynamic>` |
| UUID string | `String` | `String` |
| Enum (backed:string) | `String` (use Dart enum) | `String` |
| nullable (`?`) | `Type?` | `Type?` |

### Naming Convention

- Laravel `snake_case` → Dart `camelCase`
- Dart model files: `snake_case.dart`
- Dart classes: `PascalCase`

### JSON Serialization Notes

- Laravel models serialize via `toJson()` — uses `$hidden` (excluded), `$appends` (computed), `$casts` (type conversion)
- Only `User` model has `$hidden`: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`
- No model uses `$appends` — computed accessors must be explicitly loaded
- UUID models use `String` for `id` fields

---

## 2. Core Models

### 2.1 User

```dart
@freezed
class UserModel with _$UserModel {
  const factory UserModel({
    required int id,
    required String name,
    required String email,
    String? organizationId,
    DateTime? emailVerifiedAt,
    DateTime? twoFactorConfirmedAt,
    DateTime? createdAt,
    DateTime? updatedAt,
    // Relationships (when eager-loaded)
    OrganizationModel? organization,
    EmployeeModel? employee,
    CooperativeMemberModel? cooperativeMember,
  }) = _UserModel;

  factory UserModel.fromJson(Map<String, dynamic> json) =>
      _$UserModelFromJson(json);
}
```

**Laravel source:**
- `$fillable`: name, email, password, organization_id
- `$hidden`: password, two_factor_secret, two_factor_recovery_codes, remember_token
- `$casts`: email_verified_at → datetime, password → hashed, two_factor_confirmed_at → datetime

---

### 2.2 Employee

```dart
@freezed
class EmployeeModel with _$EmployeeModel {
  const factory EmployeeModel({
    required int id,
    int? userId,
    String? organizationId,
    required String employeeCode,
    required String firstName,
    String? lastName,
    String? email,
    String? gender, // 'M' | 'F'
    DateTime? birthDate,
    DateTime? hireDate,
    String? status,
    String? employeeType, // 'Organic' | 'TKWT'
    int? departmentId,
    int? positionId,
    int? jobGradeId,
    int? workShiftId,
    String? shiftGroup, // 'A' | 'B' | 'C' | 'D'
    String? phtkpStatus,
    String? npwpNumber,
    bool? isNpwpAvailable,
    int? numberOfDependents,
    String? bankName,
    String? bankAccountNumber,
    String? bankAccountHolder,
    // Relationships
    OrganizationModel? organization,
    DepartmentModel? department,
    PositionModel? position,
    JobGradeModel? jobGrade,
    WorkShiftModel? workShift,
  }) = _EmployeeModel;

  factory EmployeeModel.fromJson(Map<String, dynamic> json) =>
      _$EmployeeModelFromJson(json);
}
```

**Laravel source:**
- `$fillable`: user_id, organization_id, employee_code, first_name, last_name, email, gender, birth_date, hire_date, status, employee_type, department_id, position_id, job_grade_id, work_shift_id, shift_group, phtkp_status, npwp_number, is_npwp_available, number_of_dependents, bank_name, bank_account_number, bank_account_holder
- Trait: `HasOrganizationScope` (auto-scopes queries to user's organization)

---

### 2.3 Organization

```dart
@freezed
class OrganizationModel with _$OrganizationModel {
  const factory OrganizationModel({
    required String id, // UUID
    String? parentId,
    required String code,
    required String name,
    String? level, // 'L0' | 'L1' | 'L2' | 'L3'
    String? type, // 'HEAD_OFFICE' | 'REGIONAL' | 'BRANCH' | 'SITE'
    String? address,
    String? phone,
    String? email,
    @Default(true) bool isActive,
    double? latitude,
    double? longitude,
    double? radius,
  }) = _OrganizationModel;

  factory OrganizationModel.fromJson(Map<String, dynamic> json) =>
      _$OrganizationModelFromJson(json);
}
```

**Laravel source:**
- `$fillable`: parent_id, code, name, level, type, address, phone, email, is_active, latitude, longitude, radius
- `$casts`: is_active → boolean
- Trait: `HasUuids`

---

## 3. Cooperative Models

### 3.1 CooperativeMember

```dart
@freezed
class CooperativeMemberModel with _$CooperativeMemberModel {
  const factory CooperativeMemberModel({
    required int id,
    String? organizationId,
    int? employeeId,
    int? userId,
    String? memberNo,
    required String name,
    String? email,
    String? phone,
    String? identityNumber,
    String? address,
    DateTime? joinedAt,
    DateTime? resignedAt,
    String? status, // 'PENDING' | 'ACTIVE' | 'INACTIVE' | 'RESIGNED'
    String? notes,
    DateTime? createdAt,
    DateTime? updatedAt,
    // Relationships
    OrganizationModel? organization,
    EmployeeModel? employee,
    UserModel? user,
    List<CooperativeMemberDocumentModel>? documents,
    List<CooperativeDuesInvoiceModel>? invoices,
    List<CooperativePaymentModel>? payments,
    List<CooperativeLedgerEntryModel>? ledgerEntries,
  }) = _CooperativeMemberModel;

  factory CooperativeMemberModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeMemberModelFromJson(json);
}
```

### 3.2 CooperativeDuesInvoice

```dart
@freezed
class CooperativeDuesInvoiceModel with _$CooperativeDuesInvoiceModel {
  const factory CooperativeDuesInvoiceModel({
    required int id,
    required int cooperativeMemberId,
    required int cooperativeContributionTypeId,
    required String period, // 'Y-m' e.g. '2026-05'
    required double amount,
    required double paidAmount,
    DateTime? dueDate,
    String? status, // 'UNPAID' | 'PARTIAL' | 'PAID'
    // Relationships
    CooperativeMemberModel? member,
    CooperativeContributionTypeModel? contributionType,
    List<CooperativePaymentModel>? payments,
  }) = _CooperativeDuesInvoiceModel;

  factory CooperativeDuesInvoiceModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeDuesInvoiceModelFromJson(json);
}
```

### 3.3 CooperativePayment

```dart
@freezed
class CooperativePaymentModel with _$CooperativePaymentModel {
  const factory CooperativePaymentModel({
    required int id,
    required int cooperativeMemberId,
    int? cooperativeDuesInvoiceId,
    int? userId,
    required double amount,
    required String paymentMethod, // 'CASH' | 'TRANSFER' | 'QRIS'
    DateTime? paidAt,
    String? status, // 'PENDING' | 'APPROVED'
    String? proofPath,
    String? referenceNo,
    String? notes,
    DateTime? approvedAt,
    int? approvedBy,
    // Relationships
    CooperativeMemberModel? member,
    CooperativeDuesInvoiceModel? invoice,
    List<CooperativeLedgerEntryModel>? ledgerEntries,
  }) = _CooperativePaymentModel;

  factory CooperativePaymentModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativePaymentModelFromJson(json);
}
```

### 3.4 CooperativeLedgerEntry

```dart
@freezed
class CooperativeLedgerEntryModel with _$CooperativeLedgerEntryModel {
  const factory CooperativeLedgerEntryModel({
    required int id,
    required int cooperativeMemberId,
    int? cooperativePaymentId,
    String? sourceType,
    String? sourceId,
    required String entryType, // 'DEBIT' | 'CREDIT'
    required double debit,
    required double credit,
    String? period,
    String? description,
    DateTime? postedAt,
    // Relationships
    CooperativeMemberModel? member,
    CooperativePaymentModel? payment,
  }) = _CooperativeLedgerEntryModel;

  factory CooperativeLedgerEntryModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeLedgerEntryModelFromJson(json);
}
```

### 3.5 CooperativeContributionType

```dart
@freezed
class CooperativeContributionTypeModel with _$CooperativeContributionTypeModel {
  const factory CooperativeContributionTypeModel({
    required int id,
    required String code,
    required String name,
    String? category, // 'SAVINGS' | 'DUES'
    required double defaultAmount,
    String? frequency, // 'MONTHLY' | 'WEEKLY' | 'ONE_TIME'
    @Default(true) bool isActive,
  }) = _CooperativeContributionTypeModel;

  factory CooperativeContributionTypeModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeContributionTypeModelFromJson(json);
}
```

### 3.6 CooperativeMemberDocument

```dart
@freezed
class CooperativeMemberDocumentModel with _$CooperativeMemberDocumentModel {
  const factory CooperativeMemberDocumentModel({
    required int id,
    required int cooperativeMemberId,
    required String type,
    required String filePath,
    String? originalName,
  }) = _CooperativeMemberDocumentModel;

  factory CooperativeMemberDocumentModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeMemberDocumentModelFromJson(json);
}
```

### 3.7 CooperativeShuPeriod

```dart
@freezed
class CooperativeShuPeriodModel with _$CooperativeShuPeriodModel {
  const factory CooperativeShuPeriodModel({
    required int id,
    required int year,
    required double cooperativePool,
    required double posProfitPool,
    double? totalMembershipScore,
    double? totalDuesScore,
    double? totalShuScore,
    int? totalPosPoints,
    String? status, // 'OPEN' | 'CLOSED'
    DateTime? closedAt,
    int? closedBy,
  }) = _CooperativeShuPeriodModel;

  factory CooperativeShuPeriodModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeShuPeriodModelFromJson(json);
}
```

### 3.8 CooperativeShuAllocation

```dart
@freezed
class CooperativeShuAllocationModel with _$CooperativeShuAllocationModel {
  const factory CooperativeShuAllocationModel({
    required int id,
    required int cooperativeShuPeriodId,
    required int cooperativeMemberId,
    required double membershipScore,
    required double duesScore,
    required double shuScore,
    required double cooperativeShuAmount,
    required double posPoints,
    required double posShuAmount,
    required double totalAmount,
    // Relationships
    CooperativeShuPeriodModel? period,
    CooperativeMemberModel? member,
  }) = _CooperativeShuAllocationModel;

  factory CooperativeShuAllocationModel.fromJson(Map<String, dynamic> json) =>
      _$CooperativeShuAllocationModelFromJson(json);
}
```

---

## 4. POS Models

### 4.1 PosProduct

```dart
@freezed
class PosProductModel with _$PosProductModel {
  const factory PosProductModel({
    required int id,
    int? posCategoryId,
    required String sku,
    String? barcode,
    required String name,
    required double costPrice,
    required double salePrice,
    @Default(0) int stock,
    @Default(0) int minimumStock,
    @Default(true) bool isActive,
    // Relationships
    PosCategoryModel? category,
  }) = _PosProductModel;

  factory PosProductModel.fromJson(Map<String, dynamic> json) =>
      _$PosProductModelFromJson(json);
}
```

### 4.2 PosCategory

```dart
@freezed
class PosCategoryModel with _$PosCategoryModel {
  const factory PosCategoryModel({
    required int id,
    required String name,
    String? slug,
    @Default(true) bool isActive,
  }) = _PosCategoryModel;

  factory PosCategoryModel.fromJson(Map<String, dynamic> json) =>
      _$PosCategoryModelFromJson(json);
}
```

### 4.3 PosTransaction

```dart
@freezed
class PosTransactionModel with _$PosTransactionModel {
  const factory PosTransactionModel({
    required int id,
    String? transactionNo,
    String? clientReference,
    int? cooperativeMemberId,
    int? cashierId,
    required double subtotal,
    required double discountAmount,
    required double totalAmount,
    required double grossProfit,
    String? status, // 'COMPLETED' | 'VOIDED'
    DateTime? soldAt,
    // Relationships
    CooperativeMemberModel? member,
    UserModel? cashier,
    List<PosTransactionItemModel>? items,
    List<PosPaymentModel>? payments,
  }) = _PosTransactionModel;

  factory PosTransactionModel.fromJson(Map<String, dynamic> json) =>
      _$PosTransactionModelFromJson(json);
}
```

### 4.4 PosTransactionItem

```dart
@freezed
class PosTransactionItemModel with _$PosTransactionItemModel {
  const factory PosTransactionItemModel({
    required int id,
    required int posTransactionId,
    required int posProductId,
    required int quantity,
    required double unitPrice,
    required double costPrice,
    required double unitProfit,
    required double lineTotal,
    required double lineProfit,
    // Relationships
    PosProductModel? product,
  }) = _PosTransactionItemModel;

  factory PosTransactionItemModel.fromJson(Map<String, dynamic> json) =>
      _$PosTransactionItemModelFromJson(json);
}
```

### 4.5 PosPayment

```dart
@freezed
class PosPaymentModel with _$PosPaymentModel {
  const factory PosPaymentModel({
    required int id,
    required int posTransactionId,
    required String paymentMethod, // 'CASH' | 'TRANSFER' | 'QRIS'
    required double amount,
    String? referenceNo,
  }) = _PosPaymentModel;

  factory PosPaymentModel.fromJson(Map<String, dynamic> json) =>
      _$PosPaymentModelFromJson(json);
}
```

---

## 5. Technician / Work Order Models

### 5.1 WorkOrder

```dart
@freezed
class WorkOrderModel with _$WorkOrderModel {
  const factory WorkOrderModel({
    required String id, // UUID
    required String assetId,
    required String organizationId,
    required String type, // 'PREVENTIVE' | 'CORRECTIVE'
    required String priority, // 'LOW' | 'MEDIUM' | 'HIGH' | 'EMERGENCY'
    required String status, // 'OPEN' | 'IN_PROGRESS' | 'COMPLETED'
    required String description,
    int? assignedTo,
    DateTime? completedAt,
    // Relationships
    AssetModel? asset,
    OrganizationModel? organization,
    UserModel? assignedToUser,
    List<WorkOrderPartModel>? parts,
    List<WorkOrderChecklistModel>? checklists,
  }) = _WorkOrderModel;

  factory WorkOrderModel.fromJson(Map<String, dynamic> json) =>
      _$WorkOrderModelFromJson(json);
}
```

### 5.2 WorkOrderChecklist

```dart
@freezed
class WorkOrderChecklistModel with _$WorkOrderChecklistModel {
  const factory WorkOrderChecklistModel({
    required String id, // UUID
    required String workOrderId,
    required String itemName,
    String? itemDescription,
    required bool isChecked,
    String? notes,
    int? checkedBy,
    DateTime? checkedAt,
  }) = _WorkOrderChecklistModel;

  factory WorkOrderChecklistModel.fromJson(Map<String, dynamic> json) =>
      _$WorkOrderChecklistModelFromJson(json);
}
```

---

## 6. Employee Document Models

### 6.1 EmployeeCertificate

```dart
@freezed
class EmployeeCertificateModel with _$EmployeeCertificateModel {
  const factory EmployeeCertificateModel({
    required int id,
    required int employeeId,
    required String certificateType, // 'SIO_K3' | 'TRAINING' | 'OTHER'
    String? certificateTypeLabel,
    required String certificateNumber,
    required DateTime issueDate,
    DateTime? expiryDate,
    String? issuingAuthority,
    String? documentPath,
    String? documentUrl,
    required String status, // 'VALID' | 'EXPIRED' | 'REVOKED'
    String? statusLabel,
    String? notes,
    bool? isExpiring,
    bool? isExpired,
    int? daysUntilExpiry,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) = _EmployeeCertificateModel;

  factory EmployeeCertificateModel.fromJson(Map<String, dynamic> json) =>
      _$EmployeeCertificateModelFromJson(json);
}
```

> **Note:** Uses `EmployeeCertificateResource` which adds computed fields: `certificate_type_label`, `status_label`, `is_expiring`, `is_expired`, `days_until_expiry`, `document_url`.

### 6.2 MedicalCheckup

```dart
@freezed
class MedicalCheckupModel with _$MedicalCheckupModel {
  const factory MedicalCheckupModel({
    required int id,
    required int employeeId,
    required DateTime checkupDate,
    DateTime? nextCheckupDate,
    required String result, // 'FIT' | 'FIT_WITH_RESTRICTION' | 'UNFIT'
    String? resultLabel,
    String? resultColor,
    bool? fitToWork,
    String? notes,
    String? documentPath,
    String? documentUrl,
    String? doctorName,
    String? clinicName,
    bool? isDue,
    bool? isOverdue,
    int? daysUntilDue,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) = _MedicalCheckupModel;

  factory MedicalCheckupModel.fromJson(Map<String, dynamic> json) =>
      _$MedicalCheckupModelFromJson(json);
}
```

> **Note:** Uses `MedicalCheckupResource` which adds computed fields: `result_label`, `result_color`, `is_due`, `is_overdue`, `days_until_due`, `document_url`.

---

## 7. HR Models

### 7.1 Attendance

```dart
@freezed
class AttendanceModel with _$AttendanceModel {
  const factory AttendanceModel({
    required int id,
    required int employeeId,
    required String organizationId,
    required DateTime date,
    String? clockIn, // 'H:i' format
    String? clockOut,
    String? status, // 'PRESENT' | 'ABSENT' | 'SICK' | 'LEAVE' | 'OFF'
    String? notes,
    int? workShiftId,
    String? scheduledEndTime,
    bool? isOvertime,
    double? overtimeHours,
  }) = _AttendanceModel;

  factory AttendanceModel.fromJson(Map<String, dynamic> json) =>
      _$AttendanceModelFromJson(json);
}
```

### 7.2 OvertimeRequest

```dart
@freezed
class OvertimeRequestModel with _$OvertimeRequestModel {
  const factory OvertimeRequestModel({
    required String id, // UUID
    required int employeeId,
    required String organizationId,
    required int overtimeRuleId,
    required DateTime date,
    required String startTime, // 'H:i'
    required String endTime, // 'H:i'
    required double totalHours,
    String? reason,
    String? evidencePath,
    required String status, // 'PENDING' | 'APPROVED' | 'REJECTED'
    int? approvedBy,
    DateTime? approvedAt,
    String? rejectionReason,
  }) = _OvertimeRequestModel;

  factory OvertimeRequestModel.fromJson(Map<String, dynamic> json) =>
      _$OvertimeRequestModelFromJson(json);
}
```

### 7.3 Leave

```dart
@freezed
class LeaveModel with _$LeaveModel {
  const factory LeaveModel({
    required int id,
    required int employeeId,
    required int leaveTypeId,
    required DateTime startDate,
    required DateTime endDate,
    required int totalDays,
    required String reason,
    String? attachmentPath,
    required String status, // 'Pending' | 'Approved' | 'Rejected'
    int? approverId,
  }) = _LeaveModel;

  factory LeaveModel.fromJson(Map<String, dynamic> json) =>
      _$LeaveModelFromJson(json);
}
```

### 7.4 LeaveType

```dart
@freezed
class LeaveTypeModel with _$LeaveTypeModel {
  const factory LeaveTypeModel({
    required int id,
    required String name,
    required int defaultDaysAllowance,
    @Default(false) bool requiresAttachment,
    @Default(true) bool isPaid,
  }) = _LeaveTypeModel;

  factory LeaveTypeModel.fromJson(Map<String, dynamic> json) =>
      _$LeaveTypeModelFromJson(json);
}
```

### 7.5 Payroll

```dart
@freezed
class PayrollModel with _$PayrollModel {
  const factory PayrollModel({
    required int id,
    required int employeeId,
    required String organizationId,
    required String period, // 'Y-m'
    required double basicSalary,
    required double totalAllowance,
    required double totalDeduction,
    required double taxAmount,
    required double bpjsAmount,
    required double netSalary,
    String? status,
    Map<String, dynamic>? pph21CalculationBreakdown,
    double? bpjsKesehatanAmount,
    double? bpjsJhtAmount,
    double? bpjsJpAmount,
    double? bpjsJkkAmount,
    double? bpjsJkmAmount,
    Map<String, dynamic>? bpjsCalculationBreakdown,
    Map<String, dynamic>? thrCalculationBreakdown,
    // Relationships
    EmployeeModel? employee,
    List<PayrollComponentModel>? components,
  }) = _PayrollModel;

  factory PayrollModel.fromJson(Map<String, dynamic> json) =>
      _$PayrollModelFromJson(json);
}
```

### 7.6 PayrollComponent

```dart
@freezed
class PayrollComponentModel with _$PayrollComponentModel {
  const factory PayrollComponentModel({
    required int id,
    required int payrollId,
    required String type, // 'ALLOWANCE' | 'DEDUCTION'
    required String description,
    required double amount,
  }) = _PayrollComponentModel;

  factory PayrollComponentModel.fromJson(Map<String, dynamic> json) =>
      _$PayrollComponentModelFromJson(json);
}
```

---

## 8. Organization Structure Models

### 8.1 Department

```dart
@freezed
class DepartmentModel with _$DepartmentModel {
  const factory DepartmentModel({
    required int id,
    required String code,
    required String name,
    String? description,
    String? organizationId,
  }) = _DepartmentModel;

  factory DepartmentModel.fromJson(Map<String, dynamic> json) =>
      _$DepartmentModelFromJson(json);
}
```

### 8.2 Position

```dart
@freezed
class PositionModel with _$PositionModel {
  const factory PositionModel({
    required int id,
    required String code,
    required String name,
    String? description,
    required int departmentId,
    int? jobGradeId,
  }) = _PositionModel;

  factory PositionModel.fromJson(Map<String, dynamic> json) =>
      _$PositionModelFromJson(json);
}
```

### 8.3 JobGrade

```dart
@freezed
class JobGradeModel with _$JobGradeModel {
  const factory JobGradeModel({
    required int id,
    required String code,
    required String name,
    required int level,
  }) = _JobGradeModel;

  factory JobGradeModel.fromJson(Map<String, dynamic> json) =>
      _$JobGradeModelFromJson(json);
}
```

### 8.4 WorkShift

```dart
@freezed
class WorkShiftModel with _$WorkShiftModel {
  const factory WorkShiftModel({
    required int id,
    required String name,
    required String type, // 'SHIFT' | 'NON_SHIFT'
    required String startTime, // 'H:i'
    required String endTime, // 'H:i'
    @Default(false) bool isFlexible,
    @Default(0) int flexibleMinutes,
  }) = _WorkShiftModel;

  factory WorkShiftModel.fromJson(Map<String, dynamic> json) =>
      _$WorkShiftModelFromJson(json);
}
```

---

## 9. Dart Enum Definitions

### 9.1 CertificateType

```dart
enum CertificateType {
  @JsonValue('SIO_K3')
  sioK3('SIO_K3', 'SIO K3'),
  @JsonValue('TRAINING')
  training('TRAINING', 'Training'),
  @JsonValue('OTHER')
  other('OTHER', 'Other');

  final String value;
  final String label;
  const CertificateType(this.value, this.label);
}
```

### 9.2 CertificateStatus

```dart
enum CertificateStatus {
  @JsonValue('VALID')
  valid('VALID', 'Valid', 'green'),
  @JsonValue('EXPIRED')
  expired('EXPIRED', 'Expired', 'red'),
  @JsonValue('REVOKED')
  revoked('REVOKED', 'Revoked', 'gray');

  final String value;
  final String label;
  final String color;
  const CertificateStatus(this.value, this.label, this.color);
}
```

### 9.3 McuResult

```dart
enum McuResult {
  @JsonValue('FIT')
  fit('FIT', 'Fit', 'green'),
  @JsonValue('FIT_WITH_RESTRICTION')
  fitWithRestriction('FIT_WITH_RESTRICTION', 'Fit with Restriction', 'yellow'),
  @JsonValue('UNFIT')
  unfit('UNFIT', 'Unfit', 'red');

  final String value;
  final String label;
  final String color;
  const McuResult(this.value, this.label, this.color);

  bool get isFitForWork => this != McuResult.unfit;
}
```

### 9.4 CooperativeMemberStatus

```dart
enum CooperativeMemberStatus {
  @JsonValue('PENDING')
  pending,
  @JsonValue('ACTIVE')
  active,
  @JsonValue('INACTIVE')
  inactive,
  @JsonValue('RESIGNED')
  resigned;
}
```

### 9.5 PaymentMethod

```dart
enum PaymentMethod {
  @JsonValue('CASH')
  cash,
  @JsonValue('TRANSFER')
  transfer,
  @JsonValue('QRIS')
  qris,
  @JsonValue('MEMBER_CREDIT')
  memberCredit;
}
```

### 9.6 WorkOrderStatus

```dart
enum WorkOrderStatus {
  @JsonValue('OPEN')
  open,
  @JsonValue('IN_PROGRESS')
  inProgress,
  @JsonValue('COMPLETED')
  completed;
}
```

### 9.7 AttendanceStatus

```dart
enum AttendanceStatus {
  @JsonValue('PRESENT')
  present,
  @JsonValue('ABSENT')
  absent,
  @JsonValue('SICK')
  sick,
  @JsonValue('LEAVE')
  leave,
  @JsonValue('OFF')
  off;
}
```

---

## 10. Model Relationship Map

```
User
 ├── organization → Organization
 ├── employee → Employee
 ├── cooperativeMember → CooperativeMember
 └── notificationPreference → NotificationPreference

Employee
 ├── organization → Organization
 ├── user → User
 ├── department → Department
 ├── position → Position
 ├── jobGrade → JobGrade
 ├── workShift → WorkShift
 ├── attendances → Attendance[]
 ├── payrolls → Payroll[]
 ├── certificates → EmployeeCertificate[]
 ├── medicalCheckups → MedicalCheckup[]
 ├── contracts → EmployeeContract[]
 ├── families → EmployeeFamily[]
 └── transfers → EmployeeTransfer[]

CooperativeMember
 ├── organization → Organization
 ├── employee → Employee
 ├── user → User
 ├── documents → CooperativeMemberDocument[]
 ├── invoices → CooperativeDuesInvoice[]
 │    └── contributionType → CooperativeContributionType
 ├── payments → CooperativePayment[]
 │    └── invoice → CooperativeDuesInvoice
 └── ledgerEntries → CooperativeLedgerEntry[]

PosTransaction
 ├── member → CooperativeMember
 ├── cashier → User
 ├── items → PosTransactionItem[]
 │    └── product → PosProduct
 │         └── category → PosCategory
 └── payments → PosPayment[]

WorkOrder
 ├── asset → Asset
 ├── organization → Organization
 ├── assignedTo → User
 ├── checklists → WorkOrderChecklist[]
 └── parts → WorkOrderPart[]
      └── sparePart → SparePart
```

---

## 11. Key Implementation Notes

1. **`decimal:2` fields** in Laravel come as strings in JSON (e.g., `"100000.00"`). Use `double` in Dart; ensure `fromJson` does `double.parse()` or the JSON serializer handles string→double conversion.

2. **No `$appends` on any model** means computed accessors are NOT included in JSON by default. Only `EmployeeCertificateResource` and `MedicalCheckupResource` explicitly add computed fields.

3. **UUID models** — IDs are `String` type, not `int`. Models using `HasUuids` trait auto-generate UUIDs on creation. These include: Organization, WorkOrder, WorkOrderChecklist, WorkOrderPart, Asset, SparePart, and most procurement/project models.

4. **SoftDeletes** — Only `EmployeeCertificate`, `MedicalCheckup`, `Invoice`, `Reimbursement`, `PettyCashAccount`, `PettyCashTransaction`, `ProjectAssetAllocation` use soft deletes. These models will have a `deleted_at` field when deleted.

5. **Polymorphic relationships** — `CooperativeLedgerEntry.source()`, `PosStockMovement.source()`, `AuditLog.subject()` use `morphTo`. In JSON, these appear as `source_type` + `source_id` fields.

6. **Status fields are plain strings** except `EmployeeCertificate.status` (CertificateStatus enum) and `MedicalCheckup.result` (McuResult enum). All other status fields use string values directly.

---

*Dokumen ini harus diperbarui setiap ada perubahan model Laravel.*
