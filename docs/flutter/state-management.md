# State Management Patterns — Kojayaku Flutter App

**Versi:** 1.0.0
**Pattern:** Riverpod 2.x with AsyncNotifier + code generation

---

## 1. Provider Architecture

```
┌─────────────────────────────────────────────────┐
│                  Presentation                     │
│                                                   │
│  Screen Widget                                    │
│       │ ref.watch(notifierProvider)               │
│       ▼                                           │
│  XxxNotifier (AsyncNotifier)                     │
│       │ ref.read(usecaseProvider)                 │
│       ▼                                           │
│  XxxUsecase                                       │
│       │ ref.read(repositoryProvider)              │
│       ▼                                           │
│  XxxRepositoryImpl                                │
│       │ ref.read(datasourceProvider)              │
│       ▼                                           │
│  XxxRemoteDatasource                              │
│       │ ref.read(apiClientProvider)               │
│       ▼                                           │
│  ApiClient (Dio)                                  │
└─────────────────────────────────────────────────┘
```

---

## 2. Core Providers (Infrastructure)

```dart
// core/network/api_client.dart
@Riverpod(keepAlive: true)
ApiClient apiClient(ApiClientRef ref) {
  return ApiClient(ref.watch(secureStorageProvider));
}

// core/storage/secure_storage.dart
@Riverpod(keepAlive: true)
SecureStorage secureStorage(SecureStorageRef ref) {
  return SecureStorage();
}
```

---

## 3. List + Pagination Pattern

Used by: Member list, Dues invoices, Certificates, MCU records

```dart
@riverpod
class MemberList extends _$MemberList {
  int _page = 1;
  bool _hasMore = true;
  String? _search;
  String? _status;

  @override
  FutureOr<MemberListState> build() async {
    _page = 1;
    _hasMore = true;
    return _fetch();
  }

  Future<MemberListState> _fetch() async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/v1/members',
      queryParameters: {
        if (_search != null) 'search': _search,
        if (_status != null) 'status': _status,
        'page': _page,
      },
    );

    final items = (response.data['data'] as List)
        .map((e) => CooperativeMemberModel.fromJson(e))
        .toList();
    final totalPages = response.data['meta']['last_page'] as int;

    _hasMore = _page < totalPages;

    return MemberListState(
      members: items,
      hasMore: _hasMore,
      currentPage: _page,
    );
  }

  Future<void> loadMore() async {
    if (!_hasMore) return;
    final current = state.valueOrNull;
    if (current == null) return;

    _page++;
    final more = await _fetch();
    state = AsyncData(MemberListState(
      members: [...current.members, ...more.members],
      hasMore: more.hasMore,
      currentPage: more.currentPage,
    ));
  }

  void search(String query) {
    _search = query.isEmpty ? null : query;
    ref.invalidateSelf();
  }

  void filterByStatus(String? status) {
    _status = status;
    ref.invalidateSelf();
  }
}

@freezed
class MemberListState with _$MemberListState {
  const factory MemberListState({
    required List<CooperativeMemberModel> members,
    required bool hasMore,
    required int currentPage,
  }) = _MemberListState;
}
```

### Usage in Widget

```dart
class MemberListScreen extends ConsumerWidget {
  final _scrollController = ScrollController();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membersState = ref.watch(memberListProvider);

    return Scaffold(
      body: membersState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (state) => RefreshIndicator(
          onRefresh: () => ref.refresh(memberListProvider.future),
          child: ListView.builder(
            controller: _scrollController,
            itemCount: state.members.length + (state.hasMore ? 1 : 0),
            itemBuilder: (context, index) {
              if (index >= state.members.length) {
                ref.read(memberListProvider.notifier).loadMore();
                return const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              return MemberCard(member: state.members[index]);
            },
          ),
        ),
      ),
    );
  }
}
```

---

## 4. Detail + CRUD Pattern

Used by: Member detail, Certificate CRUD, MCU CRUD

```dart
@riverpod
class MemberDetail extends _$MemberDetail {
  @override
  FutureOr<CooperativeMemberModel> build(int memberId) async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/v1/members/$memberId',
    );
    return CooperativeMemberModel.fromJson(response.data['data']);
  }

  Future<void> activate() async {
    await ref.read(apiClientProvider).dio.post(
      '/api/v1/members/${state.value!.id}/activate',
    );
    ref.invalidateSelf();
  }

  Future<void> resign() async {
    await ref.read(apiClientProvider).dio.post(
      '/api/v1/members/${state.value!.id}/resign',
    );
    ref.invalidateSelf();
  }

  Future<void> update(Map<String, dynamic> data) async {
    await ref.read(apiClientProvider).dio.put(
      '/api/v1/members/${state.value!.id}',
      data: data,
    );
    ref.invalidateSelf();
  }
}
```

---

## 5. Form Submission Pattern

Used by: Create member, Record payment, Submit POS transaction

```dart
@riverpod
class PaymentForm extends _$PaymentForm {
  @override
  PaymentFormState build() {
    return const PaymentFormState.initial();
  }

  Future<void> submit(StoreCooperativePaymentRequestData data) async {
    state = const PaymentFormState.loading();

    try {
      final response = await ref.read(apiClientProvider).dio.post(
        '/api/v1/dues/payments',
        data: data.toJson(),
      );

      final payment = CooperativePaymentModel.fromJson(response.data['data']);
      state = PaymentFormState.success(payment);

      ref.invalidate(cooperativePaymentListProvider);
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response!.data['errors'] as Map<String, dynamic>;
        state = PaymentFormState.validationError(errors);
      } else {
        state = PaymentFormState.error(_mapError(e));
      }
    }
  }
}

@freezed
class PaymentFormState with _$PaymentFormState {
  const factory PaymentFormState.initial() = _Initial;
  const factory PaymentFormState.loading() = _Loading;
  const factory PaymentFormState.success(CooperativePaymentModel payment) = _Success;
  const factory PaymentFormState.validationError(Map<String, dynamic> errors) = _ValidationError;
  const factory PaymentFormState.error(String message) = _Error;
}
```

---

## 6. Optimistic Update Pattern

Used by: Checklist toggle (technician), stock adjustments

```dart
@riverpod
class ChecklistNotifier extends _$ChecklistNotifier {
  @override
  FutureOr<WorkOrderModel> build(String workOrderId) async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/technician/work-orders/$workOrderId',
    );
    return WorkOrderModel.fromJson(response.data['data']);
  }

  Future<void> toggleChecklist(String checklistId, bool isChecked) async {
    final current = state.valueOrNull;
    if (current == null) return;

    // Optimistic update
    final updatedChecklists = current.checklists?.map((c) {
      if (c.id == checklistId) {
        return c.copyWith(
          isChecked: isChecked,
          checkedBy: isChecked ? ref.read(currentUserIdProvider) : null,
          checkedAt: isChecked ? DateTime.now() : null,
        );
      }
      return c;
    }).toList();

    state = AsyncData(current.copyWith(checklists: updatedChecklists));

    try {
      await ref.read(apiClientProvider).dio.post(
        '/api/technician/work-orders/$workOrderId/checklists/$checklistId',
        data: {'is_checked': isChecked},
      );
    } catch (e) {
      // Rollback on failure
      ref.invalidateSelf();
    }
  }
}
```

---

## 7. Offline-First Pattern (Attendance)

```dart
@riverpod
class AttendanceNotifier extends _$AttendanceNotifier {
  @override
  FutureOr<AttendanceState> build() async {
    final pending = await ref.read(offlineQueueProvider).getPending();
    return AttendanceState.idle(pendingCount: pending.length);
  }

  Future<void> clockIn({
    required double latitude,
    required double longitude,
    required String photoPath,
  }) async {
    final connectivity = ref.read(connectivityProvider);

    if (connectivity == ConnectivityResult.none) {
      await _saveOffline(
        type: 'clock_in',
        latitude: latitude,
        longitude: longitude,
        photoPath: photoPath,
      );
      state = const AttendanceState.queuedOffline();
      return;
    }

    state = const AttendanceState.loading();

    try {
      final formData = FormData.fromMap({
        'latitude': latitude,
        'longitude': longitude,
        'photo': await MultipartFile.fromFile(photoPath),
      });

      final response = await ref.read(apiClientProvider).dio.post(
        '/api/attendance/clock-in',
        data: formData,
      );

      state = AttendanceState.success(AttendanceModel.fromJson(response.data));
    } on DioException catch (e) {
      state = AttendanceState.error(e.message ?? 'Gagal melakukan absensi');
    }
  }

  Future<void> _saveOffline({
    required String type,
    required double latitude,
    required double longitude,
    required String photoPath,
  }) async {
    await ref.read(offlineQueueProvider).enqueue(
      AttendanceOfflineRecord(
        type: type,
        latitude: latitude,
        longitude: longitude,
        photoPath: photoPath,
        timestamp: DateTime.now(),
        isSynced: false,
      ),
    );
  }
}
```

---

## 8. Search + Debounce Pattern

```dart
@riverpod
class ProductSearch extends _$ProductSearch {
  Timer? _debounce;

  @override
  FutureOr<List<PosProductModel>> build() async {
    return _fetch('');
  }

  Future<List<PosProductModel>> _fetch(String query) async {
    final response = await ref.read(apiClientProvider).dio.get(
      '/api/v1/pos/products',
      queryParameters: if (query.isNotEmpty) {'search': query},
    );
    return (response.data['data'] as List)
        .map((e) => PosProductModel.fromJson(e))
        .toList();
  }

  void search(String query) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      ref.invalidateSelf();
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }
}
```

---

## 9. Provider Invalidation Strategy

| Action | Strategy |
|--------|----------|
| Pull-to-refresh | `ref.refresh(provider.future)` |
| After CRUD | `ref.invalidate(listProvider)` |
| After optimistic update | `ref.invalidateSelf()` |
| After login/logout | Invalidate all scoped providers |
| Periodic sync | `ref.invalidateSelf()` on timer |

### Global Invalidation After Auth Change

```dart
@override
Future<void> logout() async {
  await ref.read(authRepositoryProvider).logout();
  // Invalidate all feature providers
  ref.invalidate(memberListProvider);
  ref.invalidate(cooperativeSummaryProvider);
  ref.invalidate(workOrderListProvider);
  // ... etc
}
```

---

## 10. Testing Patterns

### Unit Test for Notifier

```dart
void main() {
  test('MemberList search filters results', () async {
    final container = ProviderContainer(overrides: [
      apiClientProvider.overrideWithValue(mockApiClient),
    ]);

    // Setup mock response
    when(() => mockApiClient.dio.get(any(), queryParameters: any(named: 'queryParameters')))
        .thenAnswer((_) async => Response(
              data: {'data': [...], 'meta': {'last_page': 1}},
              statusCode: 200,
              requestOptions: RequestOptions(),
            ));

    final notifier = container.read(memberListProvider.notifier);
    await notifier.search('Ahmad');

    final state = container.read(memberListProvider);
    expect(state.value?.members, isNotEmpty);
  });
}
```

---

*Dokumen ini harus diperbarui jika ada perubahan pola state management.*
