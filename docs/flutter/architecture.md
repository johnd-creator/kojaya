# Architecture Document
## Kojayaku Flutter App

**Versi:** 1.0.0  
**Tanggal:** Mei 2026  
**Status:** Draft

---

## 1. Gambaran Arsitektur

Kojayaku menggunakan **Clean Architecture** yang diadaptasi untuk Flutter, dikombinasikan dengan **Feature-First** folder structure. Pola ini memastikan setiap fitur bersifat independen, mudah di-test, dan mudah dikembangkan oleh tim secara paralel.

```
┌────────────────────────────────────────────┐
│             Presentation Layer              │
│     (Screens, Widgets, Riverpod Notifiers)  │
├────────────────────────────────────────────┤
│              Domain Layer                   │
│        (Use Cases, Entities, Repos)         │
├────────────────────────────────────────────┤
│               Data Layer                    │
│    (API Client, Models, Local Storage)      │
├────────────────────────────────────────────┤
│              Core / Shared                  │
│   (Network, Theme, Router, Constants)       │
└────────────────────────────────────────────┘
```

### Prinsip Utama

1. **Dependency Inversion:** Layer atas bergantung pada abstraksi (interface), bukan implementasi konkret
2. **Separation of Concerns:** Setiap layer punya tanggung jawab yang jelas
3. **Testability:** Business logic (domain) tidak bergantung pada Flutter framework
4. **Scalability:** Fitur baru bisa ditambah tanpa mengubah fitur lain

---

## 2. Struktur Folder

```
lib/
├── core/
│   ├── network/
│   │   ├── api_client.dart          # Dio instance + interceptors
│   │   ├── api_endpoints.dart       # Semua URL endpoint
│   │   └── network_exceptions.dart  # Error handling
│   ├── storage/
│   │   ├── secure_storage.dart      # flutter_secure_storage wrapper
│   │   └── local_storage.dart       # Hive wrapper
│   ├── router/
│   │   ├── app_router.dart          # GoRouter config
│   │   └── route_names.dart         # Named routes constants
│   ├── theme/
│   │   ├── app_theme.dart
│   │   ├── app_colors.dart
│   │   └── app_text_styles.dart
│   ├── utils/
│   │   ├── date_formatter.dart
│   │   ├── currency_formatter.dart
│   │   └── validators.dart
│   └── constants/
│       └── app_constants.dart
│
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   │   └── auth_remote_datasource.dart
│   │   │   ├── models/
│   │   │   │   ├── login_request_model.dart
│   │   │   │   └── auth_token_model.dart
│   │   │   └── repositories/
│   │   │       └── auth_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   │   └── user_entity.dart
│   │   │   ├── repositories/
│   │   │   │   └── auth_repository.dart  # Abstract
│   │   │   └── usecases/
│   │   │       ├── login_usecase.dart
│   │   │       └── logout_usecase.dart
│   │   └── presentation/
│   │       ├── screens/
│   │       │   └── login_screen.dart
│   │       ├── providers/
│   │       │   └── auth_provider.dart
│   │       └── widgets/
│   │           └── login_form.dart
│   │
│   ├── anggota/
│   │   ├── dashboard/
│   │   ├── transaksi/
│   │   ├── pinjaman/
│   │   └── angsuran/
│   │
│   ├── absensi/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │
│   ├── lembur/
│   ├── cuti/
│   ├── slip_gaji/
│   ├── pengumuman/
│   └── profil/
│
└── main.dart
```

---

## 3. State Management — Riverpod 2.x

Aplikasi menggunakan **Riverpod 2.x** dengan `AsyncNotifier` dan `Notifier` sebagai pola utama.

### Hierarki Provider

```dart
// 1. Infrastructure Provider (Core)
final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());
final secureStorageProvider = Provider<SecureStorage>((ref) => SecureStorage());

// 2. Repository Provider
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(
    remoteDatasource: ref.watch(authRemoteDatasourceProvider),
    secureStorage: ref.watch(secureStorageProvider),
  );
});

// 3. UseCase Provider
final loginUsecaseProvider = Provider((ref) =>
  LoginUsecase(ref.watch(authRepositoryProvider)));

// 4. Notifier (State)
@riverpod
class AuthNotifier extends _$AuthNotifier {
  @override
  FutureOr<UserEntity?> build() async {
    return await ref.watch(authRepositoryProvider).getCurrentUser();
  }

  Future<void> login(String email, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() =>
      ref.read(loginUsecaseProvider).call(LoginParams(email, password))
    );
  }
}
```

### Pola AsyncNotifier untuk Data Fetching

```dart
@riverpod
class TransaksiNotifier extends _$TransaksiNotifier {
  @override
  FutureOr<List<TransaksiEntity>> build() async {
    return await ref.watch(getTransaksiUsecaseProvider).call();
  }

  Future<void> refresh() async {
    ref.invalidateSelf();
  }
}
```

---

## 4. Networking — Dio + Retrofit

### API Client Setup

```dart
class ApiClient {
  static const _baseUrl = String.fromEnvironment('BASE_URL',
    defaultValue: 'https://api.kojayaku.com/api/v1');

  late final Dio dio;

  ApiClient(this._secureStorage) {
    dio = Dio(BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Accept': 'application/json'},
    ));

    dio.interceptors.addAll([
      AuthInterceptor(_secureStorage),
      LogInterceptor(requestBody: true, responseBody: true),
      RetryInterceptor(dio: dio, retries: 2),
    ]);
  }
}
```

### Auth Interceptor (Token Auto-Refresh)

```dart
class AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _storage.getAccessToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      final newToken = await _refreshToken();
      if (newToken != null) {
        // Retry original request
        err.requestOptions.headers['Authorization'] = 'Bearer $newToken';
        final response = await _dio.fetch(err.requestOptions);
        return handler.resolve(response);
      }
    }
    handler.next(err);
  }
}
```

### Retrofit Service (Code Generation)

```dart
@RestApi()
abstract class AbsensiService {
  factory AbsensiService(Dio dio) = _AbsensiService;

  @POST('/absensi/masuk')
  Future<AbsensiResponse> clockIn(@Body() ClockInRequest request);

  @POST('/absensi/keluar')
  Future<AbsensiResponse> clockOut(@Body() ClockOutRequest request);

  @GET('/absensi/riwayat')
  Future<PaginatedResponse<AbsensiEntity>> getRiwayat(
    @Query('page') int page,
    @Query('per_page') int perPage,
    @Query('bulan') String? bulan,
  );
}
```

---

## 5. Navigasi — GoRouter

```dart
final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authNotifierProvider);

  return GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final isLoggedIn = authState.valueOrNull != null;
      final isAuthRoute = state.matchedLocation.startsWith('/auth');

      if (!isLoggedIn && !isAuthRoute) return '/auth/login';
      if (isLoggedIn && isAuthRoute) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
      GoRoute(
        path: '/auth',
        routes: [
          GoRoute(path: 'login', builder: (_, __) => const LoginScreen()),
        ],
      ),
      ShellRoute(
        builder: (context, state, child) => MainScaffold(child: child),
        routes: [
          GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
          GoRoute(path: '/transaksi', builder: (_, __) => const TransaksiScreen()),
          GoRoute(path: '/pinjaman', builder: (_, __) => const PinjamanScreen()),
          GoRoute(
            path: '/absensi',
            builder: (_, __) => const AbsensiScreen(),
            routes: [
              GoRoute(path: 'camera', builder: (_, __) => const AbsensiCameraScreen()),
            ],
          ),
          GoRoute(path: '/lembur', builder: (_, __) => const LemburScreen()),
          GoRoute(path: '/cuti', builder: (_, __) => const CutiScreen()),
          GoRoute(path: '/profil', builder: (_, __) => const ProfilScreen()),
        ],
      ),
    ],
  );
});
```

---

## 6. Model & Data — Freezed + JsonSerializable

Semua model data wajib menggunakan **Freezed** untuk immutability dan pattern matching.

```dart
@freezed
class PinjamanEntity with _$PinjamanEntity {
  const factory PinjamanEntity({
    required String id,
    required double jumlahPinjaman,
    required double sisaPinjaman,
    required int tenor,
    required String status,
    required DateTime tanggalPengajuan,
    DateTime? tanggalDisetujui,
  }) = _PinjamanEntity;

  factory PinjamanEntity.fromJson(Map<String, dynamic> json) =>
      _$PinjamanEntityFromJson(json);
}
```

### Sealed Class untuk State

```dart
@freezed
class AbsensiState with _$AbsensiState {
  const factory AbsensiState.initial() = _Initial;
  const factory AbsensiState.loading() = _Loading;
  const factory AbsensiState.sudahMasuk(AbsensiEntity data) = _SudahMasuk;
  const factory AbsensiState.belumMasuk() = _BelumMasuk;
  const factory AbsensiState.error(String message) = _Error;
}
```

---

## 7. Local Storage Strategy

### flutter_secure_storage — Data Sensitif

```dart
class SecureStorage {
  static const _accessTokenKey = 'access_token';
  static const _refreshTokenKey = 'refresh_token';
  static const _userDataKey = 'user_data';

  Future<void> saveTokens({
    required String accessToken,
    required String refreshToken,
  }) async {
    await Future.wait([
      _storage.write(key: _accessTokenKey, value: accessToken),
      _storage.write(key: _refreshTokenKey, value: refreshToken),
    ]);
  }
}
```

### Hive — Data Cache

```dart
// Absensi offline queue
@HiveType(typeId: 0)
class AbsensiOfflineRecord extends HiveObject {
  @HiveField(0) late String type; // 'masuk' | 'keluar'
  @HiveField(1) late double latitude;
  @HiveField(2) late double longitude;
  @HiveField(3) late String photoPath;
  @HiveField(4) late DateTime timestamp;
  @HiveField(5) late bool isSynced;
}
```

---

## 8. Fitur Offline — Absensi

```
Pengguna tap "Absensi Masuk"
          │
          ▼
    Ambil foto selfie
          │
          ▼
    Cek GPS / lokasi
          │
       ┌──┴──┐
  Online?    Offline?
    │              │
    ▼              ▼
 POST API    Simpan ke Hive
    │        (offline queue)
    ▼              │
 Success       Tampil "akan
    │          disync nanti"
    ▼              │
 Update UI         │
              Saat online:
              BackgroundSync
              POST ke API
              Update record
```

### Background Sync Worker

```dart
class AbsensiSyncService {
  Future<void> syncPendingAbsensi() async {
    final pending = await _localRepo.getPendingAbsensi();
    for (final record in pending) {
      try {
        await _remoteRepo.submitAbsensi(record);
        await _localRepo.markAsSynced(record.id);
      } catch (e) {
        // Tetap di queue, coba lagi nanti
        continue;
      }
    }
  }
}
```

---

## 9. Fitur Absensi GPS

```dart
class GpsValidationService {
  static const double _officeLatitude = -6.200000;   // sesuaikan
  static const double _officeLongitude = 106.816666; // sesuaikan
  static const double _allowedRadius = 100.0;        // meter

  Future<GpsValidationResult> validate() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      return GpsValidationResult.permissionDenied();
    }

    final position = await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
    );

    final distance = Geolocator.distanceBetween(
      position.latitude, position.longitude,
      _officeLatitude, _officeLongitude,
    );

    if (distance <= _allowedRadius) {
      return GpsValidationResult.valid(position);
    } else {
      return GpsValidationResult.tooFar(distance);
    }
  }
}
```

---

## 10. Push Notification — Firebase Cloud Messaging

```dart
class NotificationService {
  Future<void> initialize() async {
    await Firebase.initializeApp();

    // Request permission (iOS)
    await FirebaseMessaging.instance.requestPermission();

    // Simpan FCM token ke server
    final token = await FirebaseMessaging.instance.getToken();
    await _apiClient.updateFcmToken(token!);

    // Handle foreground
    FirebaseMessaging.onMessage.listen(_handleForeground);

    // Handle background tap
    FirebaseMessaging.onMessageOpenedApp.listen(_handleTap);
  }

  void _handleForeground(RemoteMessage message) {
    // Tampilkan local notification
    final notification = message.notification;
    if (notification != null) {
      _localNotifications.show(
        notification.hashCode,
        notification.title,
        notification.body,
        _buildNotificationDetails(message.data['type']),
      );
    }
  }
}
```

---

## 11. Error Handling

```dart
class NetworkException implements Exception {
  final String message;
  final int? statusCode;
  final NetworkExceptionType type;

  const NetworkException({
    required this.message,
    this.statusCode,
    required this.type,
  });

  factory NetworkException.fromDioException(DioException e) {
    return switch (e.type) {
      DioExceptionType.connectionTimeout =>
        NetworkException(message: 'Koneksi timeout', type: NetworkExceptionType.timeout),
      DioExceptionType.receiveTimeout =>
        NetworkException(message: 'Server tidak merespon', type: NetworkExceptionType.timeout),
      DioExceptionType.badResponse => switch (e.response?.statusCode) {
          401 => NetworkException(message: 'Sesi habis, silakan login ulang', statusCode: 401, type: NetworkExceptionType.unauthorized),
          403 => NetworkException(message: 'Akses ditolak', statusCode: 403, type: NetworkExceptionType.forbidden),
          422 => NetworkException(message: 'Data tidak valid', statusCode: 422, type: NetworkExceptionType.validation),
          500 => NetworkException(message: 'Kesalahan server', statusCode: 500, type: NetworkExceptionType.serverError),
          _ => NetworkException(message: 'Terjadi kesalahan', statusCode: e.response?.statusCode, type: NetworkExceptionType.unknown),
        },
      _ => NetworkException(message: 'Tidak ada koneksi internet', type: NetworkExceptionType.noConnection),
    };
  }
}
```

---

## 12. Dependency Injection

Riverpod digunakan sebagai DI container. Semua dependency didaftarkan sebagai provider di level global dan di-inject melalui `ref.watch()` / `ref.read()`.

```
main.dart
└── ProviderScope
    ├── core providers (dio, storage, router)
    ├── datasource providers
    ├── repository providers
    ├── usecase providers
    └── notifier providers (state)
```

---

## 13. CI/CD Pipeline

```
Developer push → GitHub
        │
        ▼
  GitHub Actions
        │
   ┌────┴────┐
   │  Tests  │ ← flutter test
   │         │ ← flutter analyze
   └────┬────┘
        │ (pass)
        ▼
   Build APK/IPA
   (--dart-define=ENV=staging)
        │
   ┌────┴────────────────┐
   │ Upload ke Firebase  │
   │ App Distribution    │ ← QA Testing
   └────┬────────────────┘
        │ (QA approved)
        ▼
   Build Release
   (--dart-define=ENV=production)
        │
   ┌────┴────────────────┐
   │  Google Play Store  │
   │  Apple App Store    │
   └─────────────────────┘
```

---

## 14. Tech Stack Ringkasan

| Kategori | Package | Versi |
|----------|---------|-------|
| State Management | flutter_riverpod | ^2.5.x |
| Code Generation | riverpod_generator | ^2.4.x |
| Navigation | go_router | ^14.x |
| HTTP Client | dio | ^5.x |
| API Code Gen | retrofit | ^4.x |
| Model Immutability | freezed | ^2.x |
| JSON Serialization | json_serializable | ^6.x |
| Secure Storage | flutter_secure_storage | ^9.x |
| Local DB | hive_flutter | ^1.x |
| GPS | geolocator | ^12.x |
| Camera | image_picker | ^1.x |
| Image Compress | flutter_image_compress | ^2.x |
| Push Notification | firebase_messaging | ^15.x |
| PDF Viewer | flutter_pdfview | ^1.x |
| Connectivity | connectivity_plus | ^6.x |
| Build Flavors | flutter_flavor | ^3.x |
| Crash Reporting | firebase_crashlytics | ^4.x |
| Analytics | firebase_analytics | ^11.x |

---

## 15. Konvensi Kode

- **Naming:** `snake_case` untuk file, `PascalCase` untuk class, `camelCase` untuk variabel
- **Comment:** Wajib untuk semua method public di domain layer
- **Lint:** Gunakan `flutter_lints` + custom rules di `analysis_options.yaml`
- **Git Branch:** `main` (production), `develop`, `feature/nama-fitur`, `fix/nama-bug`
- **Commit Message:** Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`)

---

*Dokumen ini adalah panduan teknis utama dan harus diperbarui setiap ada perubahan arsitektur signifikan.*
