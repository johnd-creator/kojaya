# Authentication Guide — Kojayaku Flutter App

**Versi:** 1.0.0
**Auth System:** Laravel Sanctum (Token-Based)

---

## 1. Architecture Overview

```
┌──────────────────────────────────────────────────────┐
│                    Flutter App                        │
│                                                      │
│  ┌─────────┐    ┌──────────────┐    ┌────────────┐  │
│  │ Login   │───>│ Sanctum API  │───>│ Bearer     │  │
│  │ Screen  │    │ Token Issue  │    │ Token      │  │
│  └─────────┘    └──────────────┘    └─────┬──────┘  │
│                                          │          │
│                              ┌───────────▼────────┐ │
│                              │ Secure Storage      │ │
│                              │ (flutter_secure_    │ │
│                              │  storage)           │ │
│                              └───────────┬────────┘ │
│                                          │          │
│  ┌──────────────────────────────────────▼───────┐  │
│  │              Auth Interceptor (Dio)           │  │
│  │  • Auto-attach Bearer token to all requests  │  │
│  │  • Handle 401 → force re-login               │  │
│  └──────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────┐
│                  Laravel Backend                      │
│                                                      │
│  Fortify: Login / Register / Password Reset / 2FA    │
│  Sanctum: Token management with abilities            │
│  Expiration: 30 days (43,200 minutes)                │
│  Rate Limit: 5 login attempts/min per email+IP       │
└──────────────────────────────────────────────────────┘
```

---

## 2. Token Configuration

| Setting | Value |
|---------|-------|
| Expiration | 30 days (`43,200 minutes`) |
| Guard | `web` |
| Storage | `flutter_secure_storage` (encrypted) |
| Prefix | Configurable via `SANCTUM_TOKEN_PREFIX` |

### Token Abilities

Tokens are scoped to specific abilities. When creating a token, request only the abilities needed:

```dart
enum TokenAbility {
  profileRead('profile:read'),
  cooperativeRead('cooperative:read'),
  cooperativeWrite('cooperative:write'),
  posRead('pos:read'),
  posWrite('pos:write'),
  reportsRead('reports:read'),
  workOrdersRead('work-orders:read'),
  workOrdersWrite('work-orders:write'),
  employeeDocumentsRead('employee-documents:read'),
  employeeDocumentsWrite('employee-documents:write');

  final String value;
  const TokenAbility(this.value);
}
```

---

## 3. Authentication Flow

### 3.1 Login Flow

```
User opens app
      │
      ▼
Check secure storage for token
      │
  ┌───┴───┐
  │Exists? │
  └───┬───┘
   No │         Yes
      │          │
      ▼          ▼
  Login      Validate token
  Screen     GET /api/user
      │          │
      │     ┌────┴────┐
      │     │ Valid?  │
      │     └────┬────┘
      │      Yes │     No
      │          │     │
      │          ▼     ▼
      │     Dashboard  Login
      │                Screen
      ▼
 POST /login
 (email + password)
      │
  ┌───┴────┐
  │Success?│
  └───┬────┘
   Yes│     No
      │     │
      ▼     ▼
  Store   Show error
  token   message
      │
      ▼
  Dashboard
```

### 3.2 Implementation

```dart
class AuthRepositoryImpl implements AuthRepository {
  final ApiClient _apiClient;
  final SecureStorage _secureStorage;

  static const _accessTokenKey = 'access_token';
  static const _userDataKey = 'user_data';

  @override
  Future<UserModel> login({
    required String email,
    required String password,
    required List<String> abilities,
  }) async {
    final response = await _apiClient.dio.post(
      '/login',
      data: {
        'email': email,
        'password': password,
        'abilities': abilities,
      },
      options: Options(
        headers: {'Accept': 'application/json'},
      ),
    );

    final token = response.data['token'] as String?;
    if (token == null) {
      throw const AuthException('Token not received');
    }

    await _secureStorage.write(key: _accessTokenKey, value: token);

    final userResponse = await _apiClient.dio.get(
      '/api/user',
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );

    final user = UserModel.fromJson(userResponse.data);
    await _secureStorage.write(
      key: _userDataKey,
      value: jsonEncode(user.toJson()),
    );

    return user;
  }

  @override
  Future<void> logout() async {
    try {
      await _apiClient.dio.post('/logout');
    } finally {
      await _secureStorage.delete(key: _accessTokenKey);
      await _secureStorage.delete(key: _userDataKey);
    }
  }

  @override
  Future<UserModel?> getCurrentUser() async {
    final userData = await _secureStorage.read(key: _userDataKey);
    if (userData == null) return null;

    try {
      return UserModel.fromJson(jsonDecode(userData));
    } catch (_) {
      return null;
    }
  }

  @override
  Future<bool> validateToken() async {
    try {
      await _apiClient.dio.get('/api/user');
      return true;
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await logout();
        return false;
      }
      rethrow;
    }
  }
}
```

---

## 4. Auth Interceptor

```dart
class AuthInterceptor extends Interceptor {
  final SecureStorage _storage;

  AuthInterceptor(this._storage);

  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _storage.read(key: 'access_token');
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      await _storage.delete(key: 'access_token');
      await _storage.delete(key: 'user_data');
    }
    handler.next(err);
  }
}
```

---

## 5. Auth State Management (Riverpod)

```dart
@riverpod
class AuthNotifier extends _$AuthNotifier {
  @override
  FutureOr<UserModel?> build() async {
    return ref.read(authRepositoryProvider).getCurrentUser();
  }

  Future<void> login(String email, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() {
      return ref.read(authRepositoryProvider).login(
        email: email,
        password: password,
        abilities: [
          'profile:read',
          'cooperative:read',
          'cooperative:write',
          'pos:read',
          'pos:write',
          'reports:read',
          'work-orders:read',
          'work-orders:write',
          'employee-documents:read',
          'employee-documents:write',
        ],
      );
    });
  }

  Future<void> logout() async {
    await ref.read(authRepositoryProvider).logout();
    state = const AsyncData(null);
  }
}
```

---

## 6. Route Guards (GoRouter)

```dart
final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authNotifierProvider);

  return GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final isLoggedIn = authState.valueOrNull != null;
      final isAuthRoute = state.matchedLocation.startsWith('/auth');
      final isSplash = state.matchedLocation == '/splash';

      if (isSplash) return null;

      if (!isLoggedIn && !isAuthRoute) return '/auth/login';
      if (isLoggedIn && isAuthRoute) return '/home';
      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, __) => const SplashScreen(),
      ),
      GoRoute(
        path: '/auth',
        routes: [
          GoRoute(
            path: 'login',
            builder: (_, __) => const LoginScreen(),
          ),
          GoRoute(
            path: 'forgot-password',
            builder: (_, __) => const ForgotPasswordScreen(),
          ),
        ],
      ),
      ShellRoute(
        builder: (context, state, child) => MainScaffold(child: child),
        routes: [
          GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
          // ... other routes
        ],
      ),
    ],
  );
});
```

---

## 7. Password Reset Flow

```
User taps "Forgot Password"
      │
      ▼
POST /forgot-password { email }
      │
      ▼
Server sends reset link to email
      │
      ▼
User opens email → gets token
      │
      ▼
Deep link to app:
/auth/reset-password?token=xxx&email=xxx
      │
      ▼
POST /reset-password { token, email, password, password_confirmation }
      │
      ▼
Redirect to login
```

### Deep Link Configuration

**Android (`android/app/src/main/AndroidManifest.xml`):**
```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW"/>
    <category android:name="android.intent.category.DEFAULT"/>
    <category android:name="android.intent.category.BROWSABLE"/>
    <data android:scheme="kojayaku" android:host="reset-password"/>
</intent-filter>
```

**iOS (`ios/Runner/Info.plist`):**
```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>kojayaku</string>
        </array>
    </dict>
</array>
```

**Laravel `.env`:**
```
APP_URL=https://kojayaku.com
FRONTEND_URL=kojayaku://reset-password
```

---

## 8. Biometric Login

```dart
class BiometricAuthService {
  final LocalAuthentication _localAuth = LocalAuthentication();
  final SecureStorage _secureStorage;

  Future<bool> isBiometricAvailable() async {
    return await _localAuth.canCheckBiometrics &&
        await _localAuth.isDeviceSupported();
  }

  Future<bool> authenticateWithBiometrics() async {
    try {
      return await _localAuth.authenticate(
        localizedReason: 'Autentikasi untuk mengakses Kojayaku',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
    } catch (_) {
      return false;
    }
  }

  Future<void> enableBiometricLogin(String email, String password) async {
    final authenticated = await authenticateWithBiometrics();
    if (authenticated) {
      await _secureStorage.write(key: 'biometric_email', value: email);
      await _secureStorage.write(key: 'biometric_password', value: password);
      await _secureStorage.write(key: 'biometric_enabled', value: 'true');
    }
  }

  Future<UserModel?> loginWithBiometric(AuthRepository authRepo) async {
    final enabled = await _secureStorage.read(key: 'biometric_enabled');
    if (enabled != 'true') return null;

    final authenticated = await authenticateWithBiometrics();
    if (!authenticated) return null;

    final email = await _secureStorage.read(key: 'biometric_email');
    final password = await _secureStorage.read(key: 'biometric_password');

    if (email == null || password == null) return null;

    return await authRepo.login(
      email: email,
      password: password,
      abilities: TokenAbility.values.map((a) => a.value).toList(),
    );
  }
}
```

---

## 9. Role-Based UI

### Determining User Role

```dart
@riverpod
Future<UserRole> userRole(UserRoleRef ref) async {
  final user = ref.watch(authNotifierProvider).valueOrNull;
  if (user == null) return UserRole.none;

  final response = await ref.read(apiClientProvider).dio.get(
    '/api/user',
  );

  final roles = response.data['roles'] as List<dynamic>?;
  if (roles == null || roles.isEmpty) return UserRole.none;

  final roleName = roles.first['name'] as String?;
  return switch (roleName) {
    'System Admin' => UserRole.systemAdmin,
    'Pengurus Koperasi' => UserRole.cooperativeAdmin,
    'Kasir Koperasi' => UserRole.cooperativeCashier,
    'Anggota' => UserRole.cooperativeMember,
    'Teknisi' => UserRole.technician,
    _ => UserRole.employee,
  };
}

enum UserRole {
  none,
  systemAdmin,
  cooperativeAdmin,
  cooperativeCashier,
  cooperativeMember,
  technician,
  employee,
}
```

### Bottom Navigation by Role

| Role | Tabs |
|------|------|
| `cooperativeMember` | Beranda, Transaksi, Pinjaman, Profil |
| `cooperativeCashier` | Beranda, Kasir POS, Stok, Profil |
| `technician` | Beranda, Work Orders, Profil |
| `employee` | Beranda, Absensi, Pengajuan, Profil |
| `systemAdmin` / `cooperativeAdmin` | Beranda, Anggota, Keuangan, Laporan, Profil |

---

## 10. Security Best Practices

1. **Always use HTTPS** — Set `baseUrl` to `https://` in production
2. **Never log tokens** — Ensure debug logs don't contain auth tokens
3. **Clear storage on logout** — Delete token AND user data from secure storage
4. **Token rotation** — Consider re-authenticating periodically
5. **Biometric guard** — Store credentials in `flutter_secure_storage` (hardware-backed keystore/keychain)
6. **Certificate pinning** — Consider adding SSL pinning for production
7. **Obfuscate builds** — Use `--obfuscate --split-debug-info` for release builds

---

## 11. Token Creation Endpoint (Backend Note)

The current Laravel codebase does not have a dedicated API token creation endpoint for mobile. You will need to add one:

```php
// routes/api.php — Add before the auth:sanctum middleware group
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Kredensial yang diberikan salah.'],
        ]);
    }

    $abilities = $request->input('abilities', ['profile:read']);
    $token = $user->createToken('mobile-app', $abilities)->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
})->middleware('throttle:login');
```

---

*Dokumen ini harus diperbarui jika ada perubahan pada sistem autentikasi.*
