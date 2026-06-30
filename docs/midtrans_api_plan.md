# Midtrans Payment Integration Plan — Kojaya ERP

> **Untuk AI Assistant (Claude Code / Cursor / Copilot / dll.)**
> Dokumen ini adalah **rencana eksekusi lengkap** untuk mengintegrasikan Midtrans Payment Gateway ke dalam project Kojaya.
> Baca seluruh dokumen ini sebelum menulis satu baris kode pun.

---

## 📋 Context Project

| Item | Detail |
|---|---|
| **Framework** | Laravel 12 + Vue 3 + Inertia.js (Wayfinder pattern) |
| **Frontend** | TypeScript, shadcn-vue, Tailwind CSS v4 |
| **Database** | PostgreSQL — semua PK menggunakan **UUID** (`HasUuids` trait) |
| **Auth** | Laravel Fortify + Sanctum |
| **Roles** | Spatie Laravel Permission |
| **Queue** | Saat ini `sync` (bisa diubah ke database/redis jika diperlukan) |
| **Existing env vars** | `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` sudah ada di `.env.example` |
| **Arsitektur** | Services layer di `app/Services/`, Invokable controllers di `app/Actions/` |

---

## 🎯 Scope Integrasi

Integrasi Midtrans mencakup use-case berikut dalam konteks koperasi:

1. **Simpanan Anggota** — Anggota membayar simpanan wajib/sukarela via payment gateway
2. **Cicilan Pinjaman** — Anggota membayar angsuran pinjaman
3. **Tagihan / Invoice** — Pembayaran invoice yang sudah ada di modul invoicing

> Jika hanya sebagian scope yang akan dieksekusi, tandai item yang di-skip dan beri alasan di `docs/decisions.md`.

---

## 🏗️ Arsitektur yang Harus Dibangun

```
app/
├── Services/
│   └── Payment/
│       ├── MidtransService.php          ← Core service (create token, check status)
│       └── MidtransWebhookService.php   ← Verifikasi & proses notifikasi
├── Actions/
│   ├── Payment/
│   │   ├── CreatePaymentAction.php      ← Invokable: buat transaksi & Snap token
│   │   ├── CheckPaymentStatusAction.php ← Invokable: cek status ke Midtrans
│   │   └── HandleWebhookAction.php      ← Invokable: endpoint webhook
├── Models/
│   └── Payment.php                      ← Model transaksi pembayaran
├── Enums/
│   └── PaymentStatus.php               ← Enum status pembayaran
├── Http/
│   └── Requests/
│       └── CreatePaymentRequest.php
database/
└── migrations/
    └── xxxx_create_payments_table.php
resources/js/
└── pages/
    └── Payment/
        ├── Create.vue                   ← Halaman inisiasi pembayaran + Snap popup
        └── Status.vue                   ← Halaman konfirmasi status
config/
└── midtrans.php                        ← Config file baru
```

---

## 📦 Step 1 — Instalasi Package

```bash
composer require midtrans/midtrans-php
```

> **Catatan:** Tidak ada official Laravel wrapper yang wajib digunakan. Package `midtrans/midtrans-php` adalah SDK resmi dan cukup.

---

## ⚙️ Step 2 — Konfigurasi

### 2.1 Buat `config/midtrans.php`

```php
<?php

return [
    'server_key'    => env('MIDTRANS_SERVER_KEY', ''),
    'client_key'    => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized'  => true,
    'is_3ds'        => true,

    // Snap URL
    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',
];
```

### 2.2 Update `.env.example`

Tambahkan baris berikut (keys sudah ada, tambahkan yang kurang):

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

### 2.3 Register config di `AppServiceProvider`

Di `app/Providers/AppServiceProvider.php`, dalam method `register()`:

```php
// Tidak perlu manual binding — cukup gunakan config() helper di service.
// Pastikan config/midtrans.php sudah ada dan terdaftar otomatis oleh Laravel.
```

---

## 🗄️ Step 3 — Migration & Model

### 3.1 Migration `payments`

```php
// database/migrations/xxxx_create_payments_table.php

Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('order_id')->unique();          // Format: PAY-{timestamp}-{random}
    $table->string('snap_token')->nullable();       // Token dari Midtrans Snap
    $table->string('payment_type')->nullable();     // gopay, bank_transfer, dll.
    $table->string('transaction_id')->nullable();   // ID dari Midtrans
    $table->string('status');                       // Pakai PaymentStatus enum
    $table->decimal('gross_amount', 15, 2);
    $table->string('currency', 3)->default('IDR');
    $table->string('description')->nullable();

    // Polymorphic: bisa untuk simpanan, cicilan, atau invoice
    $table->uuidMorphs('payable');                 // payable_type + payable_id

    // Relasi ke member/user
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

    $table->json('midtrans_response')->nullable();  // Raw response dari Midtrans
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['status', 'created_at']);
    $table->index(['user_id', 'status']);
});
```

### 3.2 Model `app/Models/Payment.php`

```php
<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'order_id', 'snap_token', 'payment_type', 'transaction_id',
        'status', 'gross_amount', 'currency', 'description',
        'payable_type', 'payable_id', 'user_id',
        'midtrans_response', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status'            => PaymentStatus::class,
            'gross_amount'      => 'decimal:2',
            'midtrans_response' => 'array',
            'paid_at'           => 'datetime',
            'is_production'     => 'boolean',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }
}
```

### 3.3 Enum `app/Enums/PaymentStatus.php`

```php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Failed   = 'failed';
    case Expired  = 'expired';
    case Cancelled = 'cancelled';
    case Challenge = 'challenge'; // Midtrans fraud challenge

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Menunggu Pembayaran',
            self::Paid      => 'Lunas',
            self::Failed    => 'Gagal',
            self::Expired   => 'Kadaluarsa',
            self::Cancelled => 'Dibatalkan',
            self::Challenge => 'Perlu Verifikasi',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'warning',
            self::Paid      => 'success',
            self::Failed    => 'destructive',
            self::Expired   => 'secondary',
            self::Cancelled => 'secondary',
            self::Challenge => 'warning',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Failed, self::Expired, self::Cancelled]);
    }
}
```

---

## 🔧 Step 4 — Service Layer

### 4.1 `app/Services/Payment/MidtransService.php`

```php
<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\User;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = config('midtrans.is_sanitized');
        Config::$is3ds        = config('midtrans.is_3ds');
    }

    /**
     * Buat Snap token untuk pembayaran baru.
     */
    public function createSnapToken(Payment $payment, User $user): string
    {
        $params = [
            'transaction_details' => [
                'order_id'     => $payment->order_id,
                'gross_amount' => (int) $payment->gross_amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $payment->payable_type . '-' . $payment->payable_id,
                    'price'    => (int) $payment->gross_amount,
                    'quantity' => 1,
                    'name'     => $payment->description ?? 'Pembayaran Koperasi',
                ],
            ],
            'callbacks' => [
                'finish'  => route('payment.status', $payment->order_id),
                'error'   => route('payment.status', $payment->order_id),
                'pending' => route('payment.status', $payment->order_id),
            ],
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Cek status transaksi langsung ke Midtrans.
     */
    public function checkTransactionStatus(string $orderId): object
    {
        return Transaction::status($orderId);
    }

    /**
     * Generate order_id yang unik.
     */
    public function generateOrderId(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
```

### 4.2 `app/Services/Payment/MidtransWebhookService.php`

```php
<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransWebhookService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
    }

    /**
     * Proses notifikasi dari Midtrans.
     * Mengembalikan Payment yang diupdate, atau null jika tidak valid.
     */
    public function handle(array $payload): ?Payment
    {
        // Verifikasi signature key
        $signatureKey = hash('sha512',
            $payload['order_id'] .
            $payload['status_code'] .
            $payload['gross_amount'] .
            config('midtrans.server_key')
        );

        if ($signatureKey !== $payload['signature_key']) {
            Log::warning('Midtrans webhook: invalid signature', ['order_id' => $payload['order_id'] ?? null]);
            return null;
        }

        $payment = Payment::where('order_id', $payload['order_id'])->first();

        if (! $payment) {
            Log::warning('Midtrans webhook: payment not found', ['order_id' => $payload['order_id']]);
            return null;
        }

        // Jangan proses ulang status yang sudah terminal
        if ($payment->status->isTerminal()) {
            return $payment;
        }

        $newStatus = $this->mapStatus(
            $payload['transaction_status'],
            $payload['fraud_status'] ?? null
        );

        $payment->update([
            'status'           => $newStatus,
            'payment_type'     => $payload['payment_type'] ?? $payment->payment_type,
            'transaction_id'   => $payload['transaction_id'] ?? $payment->transaction_id,
            'midtrans_response'=> $payload,
            'paid_at'          => $newStatus === PaymentStatus::Paid ? now() : $payment->paid_at,
        ]);

        // Trigger event untuk downstream action (update simpanan, cicilan, invoice)
        if ($newStatus === PaymentStatus::Paid) {
            event(new \App\Events\PaymentSucceeded($payment));
        }

        return $payment->fresh();
    }

    private function mapStatus(string $transactionStatus, ?string $fraudStatus): PaymentStatus
    {
        return match(true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => PaymentStatus::Paid,
            $transactionStatus === 'settlement'                           => PaymentStatus::Paid,
            $transactionStatus === 'pending'                              => PaymentStatus::Pending,
            $transactionStatus === 'deny'                                 => PaymentStatus::Failed,
            $transactionStatus === 'cancel'                               => PaymentStatus::Cancelled,
            $transactionStatus === 'expire'                               => PaymentStatus::Expired,
            $transactionStatus === 'challenge'                            => PaymentStatus::Challenge,
            default                                                       => PaymentStatus::Pending,
        };
    }
}
```

---

## 🎬 Step 5 — Actions (Invokable Controllers)

### 5.1 `app/Actions/Payment/CreatePaymentAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Http\Requests\CreatePaymentRequest;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use App\Services\Payment\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreatePaymentAction
{
    public function __construct(private readonly MidtransService $midtrans) {}

    public function __invoke(CreatePaymentRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $payable = $request->getPayable(); // Resolve polymorphic model

            $payment = Payment::create([
                'order_id'     => $this->midtrans->generateOrderId(),
                'gross_amount' => $request->validated('amount'),
                'description'  => $request->validated('description'),
                'status'       => PaymentStatus::Pending,
                'payable_type' => get_class($payable),
                'payable_id'   => $payable->id,
                'user_id'      => $request->user()->id,
            ]);

            $snapToken = $this->midtrans->createSnapToken($payment, $request->user());

            $payment->update(['snap_token' => $snapToken]);

            return response()->json([
                'snap_token' => $snapToken,
                'order_id'   => $payment->order_id,
                'client_key' => config('midtrans.client_key'),
                'snap_url'   => config('midtrans.snap_url'),
            ]);
        });
    }
}
```

### 5.2 `app/Actions/Payment/HandleWebhookAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Services\Payment\MidtransWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandleWebhookAction
{
    public function __construct(private readonly MidtransWebhookService $webhookService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payment = $this->webhookService->handle($request->all());

        if (! $payment) {
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
```

### 5.3 `app/Actions/Payment/CheckPaymentStatusAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Services\Payment\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckPaymentStatusAction
{
    public function __construct(private readonly MidtransService $midtrans) {}

    public function __invoke(Request $request, string $orderId): Response
    {
        $payment = Payment::where('order_id', $orderId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return Inertia::render('Payment/Status', [
            'payment' => [
                'order_id'    => $payment->order_id,
                'status'      => $payment->status->value,
                'status_label'=> $payment->status->label(),
                'amount'      => $payment->gross_amount,
                'description' => $payment->description,
                'paid_at'     => $payment->paid_at,
            ],
        ]);
    }
}
```

---

## 🌐 Step 6 — Routes

Di `routes/web.php`, tambahkan:

```php
use App\Actions\Payment\CreatePaymentAction;
use App\Actions\Payment\CheckPaymentStatusAction;

Route::middleware(['auth', 'verified'])->prefix('payment')->name('payment.')->group(function () {
    Route::post('/create', CreatePaymentAction::class)->name('create');
    Route::get('/status/{orderId}', CheckPaymentStatusAction::class)->name('status');
});
```

Di `routes/api.php`, tambahkan route webhook (TANPA auth middleware):

```php
use App\Actions\Payment\HandleWebhookAction;

// Webhook Midtrans — JANGAN gunakan auth middleware
// HARUS dikecualikan dari CSRF verification
Route::post('/midtrans/webhook', HandleWebhookAction::class)->name('midtrans.webhook');
```

### Kecualikan dari CSRF di `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/midtrans/webhook',
    ]);
})
```

---

## 💻 Step 7 — Frontend Vue 3

### 7.1 `resources/js/pages/Payment/Create.vue`

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// Props dari controller (via Inertia::render)
const props = defineProps<{
  payableType: string
  payableId: string
  amount: number
  description: string
}>()

const isLoading = ref(false)
const error = ref<string | null>(null)

async function initiatePayment() {
  isLoading.value = true
  error.value = null

  try {
    const { data } = await axios.post(route('payment.create'), {
      payable_type: props.payableType,
      payable_id: props.payableId,
      amount: props.amount,
      description: props.description,
    })

    // Load Midtrans Snap script secara dinamis
    await loadSnapScript(data.snap_url, data.client_key)

    // Buka popup Snap
    window.snap.pay(data.snap_token, {
      onSuccess(result: object) {
        router.visit(route('payment.status', { orderId: data.order_id }))
      },
      onPending(result: object) {
        router.visit(route('payment.status', { orderId: data.order_id }))
      },
      onError(result: object) {
        error.value = 'Pembayaran gagal. Silakan coba lagi.'
      },
      onClose() {
        // User menutup popup tanpa menyelesaikan pembayaran
        isLoading.value = false
      },
    })
  } catch (err) {
    error.value = 'Terjadi kesalahan. Silakan coba lagi.'
  } finally {
    isLoading.value = false
  }
}

function loadSnapScript(snapUrl: string, clientKey: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.getElementById('midtrans-snap')) {
      resolve()
      return
    }
    const script = document.createElement('script')
    script.id = 'midtrans-snap'
    script.src = snapUrl
    script.setAttribute('data-client-key', clientKey)
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap'))
    document.head.appendChild(script)
  })
}
</script>

<template>
  <div>
    <p class="text-sm text-muted-foreground">{{ description }}</p>
    <p class="text-2xl font-semibold">Rp {{ amount.toLocaleString('id-ID') }}</p>

    <p v-if="error" class="text-destructive text-sm mt-2">{{ error }}</p>

    <Button :disabled="isLoading" @click="initiatePayment">
      {{ isLoading ? 'Memproses...' : 'Bayar Sekarang' }}
    </Button>
  </div>
</template>
```

> **Tambahkan type declaration** untuk `window.snap` di `resources/js/types/midtrans.d.ts`:
> ```typescript
> interface Window {
>   snap: {
>     pay(token: string, options: Record<string, unknown>): void
>   }
> }
> ```

---

## 🔔 Step 8 — Event & Listener (Downstream Action)

### 8.1 Buat Event `app/Events/PaymentSucceeded.php`

```php
<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentSucceeded
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment) {}
}
```

### 8.2 Buat Listener `app/Listeners/UpdatePayableStatusOnPayment.php`

```php
<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;

class UpdatePayableStatusOnPayment
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;
        $payable = $payment->payable;

        // Dispatch ke method yang sesuai berdasarkan payable type
        // Contoh: update status simpanan, tandai cicilan lunas, dll.
        if (method_exists($payable, 'markAsPaid')) {
            $payable->markAsPaid($payment);
        }
    }
}
```

### 8.3 Register di `EventServiceProvider` atau `AppServiceProvider`

```php
Event::listen(PaymentSucceeded::class, UpdatePayableStatusOnPayment::class);
```

---

## 🔐 Step 9 — Form Request Validation

### `app/Http/Requests/CreatePaymentRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gunakan Gate/Policy jika perlu otorisasi spesifik
    }

    public function rules(): array
    {
        return [
            'payable_type' => ['required', 'string', Rule::in([
                'App\\Models\\Simpanan',
                'App\\Models\\CicilanPinjaman',
                'App\\Models\\Invoice',
            ])],
            'payable_id'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric', 'min:1000'], // Min Rp 1.000
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function getPayable(): \Illuminate\Database\Eloquent\Model
    {
        $class = $this->validated('payable_type');
        return $class::findOrFail($this->validated('payable_id'));
    }
}
```

---

## 🧪 Step 10 — Testing

### 10.1 Feature Test `tests/Feature/Payment/MidtransWebhookTest.php`

```php
<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));
    }

    public function test_webhook_marks_payment_as_paid(): void
    {
        $user    = User::factory()->create();
        $payment = Payment::factory()->create([
            'user_id'      => $user->id,
            'order_id'     => 'PAY-TEST-001',
            'gross_amount' => 100000,
            'status'       => PaymentStatus::Pending,
        ]);

        $payload = [
            'order_id'           => 'PAY-TEST-001',
            'status_code'        => '200',
            'gross_amount'       => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'payment_type'       => 'bank_transfer',
            'transaction_id'     => 'mid-txn-001',
            'signature_key'      => $this->makeSignature('PAY-TEST-001', '200', '100000.00'),
        ];

        $this->postJson(route('midtrans.webhook'), $payload)
            ->assertOk()
            ->assertJson(['message' => 'OK']);

        $this->assertDatabaseHas('payments', [
            'order_id' => 'PAY-TEST-001',
            'status'   => PaymentStatus::Paid->value,
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = [
            'order_id'     => 'PAY-TEST-001',
            'status_code'  => '200',
            'gross_amount' => '100000.00',
            'signature_key'=> 'invalid-signature',
        ];

        $this->postJson(route('midtrans.webhook'), $payload)
            ->assertStatus(400);
    }
}
```

---

## ✅ Checklist Eksekusi (Urutan Wajib)

Ikuti urutan ini — jangan skip langkah:

- [ ] `composer require midtrans/midtrans-php`
- [ ] Buat `config/midtrans.php`
- [ ] Update `.env` dengan Sandbox keys dari dashboard Midtrans
- [ ] Buat migration `payments` dan jalankan `php artisan migrate`
- [ ] Buat `PaymentStatus` enum
- [ ] Buat `Payment` model
- [ ] Buat `MidtransService`
- [ ] Buat `MidtransWebhookService`
- [ ] Buat `CreatePaymentAction`, `HandleWebhookAction`, `CheckPaymentStatusAction`
- [ ] Buat `CreatePaymentRequest`
- [ ] Daftarkan routes di `web.php` dan `api.php`
- [ ] Kecualikan webhook dari CSRF di `bootstrap/app.php`
- [ ] Buat `PaymentSucceeded` event dan `UpdatePayableStatusOnPayment` listener
- [ ] Buat type declaration `window.snap` di `resources/js/types/midtrans.d.ts`
- [ ] Buat Vue page `Payment/Create.vue` dan `Payment/Status.vue`
- [ ] Tulis Feature Tests
- [ ] Jalankan `vendor/bin/pint --dirty --format agent` (PHP lint)
- [ ] Jalankan `npm run lint` (TS/Vue lint)
- [ ] Jalankan `php artisan test --compact` — pastikan semua hijau
- [ ] Test end-to-end di Sandbox menggunakan test card/VA Midtrans

---

## ⚠️ Hal-hal Kritis yang Harus Diperhatikan

### Keamanan
- **JANGAN** pernah expose `MIDTRANS_SERVER_KEY` ke frontend. Hanya `MIDTRANS_CLIENT_KEY` yang boleh di-pass ke Vue/JS.
- Webhook endpoint **HARUS** memverifikasi `signature_key` sebelum memproses apapun.
- Webhook **TIDAK boleh** menggunakan auth middleware (Midtrans server yang memanggil, bukan user).
- Webhook HARUS dikecualikan dari CSRF protection.

### Konsistensi Data
- Gunakan **database transaction** saat membuat Payment record + request ke Midtrans.
- Jangan update status payment berdasarkan response frontend saja — selalu andalkan webhook server-side.
- Cek `isTerminal()` pada status sebelum update untuk menghindari race condition.

### UUID
- Semua FK ke model lain menggunakan UUID (`foreignUuid()`), konsisten dengan konvensi project ini.
- `order_id` adalah string unik (bukan UUID) — gunakan format `PAY-{YmdHis}-{random}`.

### Wayfinder
- Gunakan `route()` helper Laravel untuk generate URL di Actions (backend).
- Di Vue, gunakan `route()` dari `@/routes/` (Wayfinder-generated) agar type-safe.

---

## 📚 Referensi

- [Midtrans Snap Documentation](https://docs.midtrans.com/reference/snap-overview)
- [Midtrans Webhook / HTTP Notification](https://docs.midtrans.com/reference/receiving-response-and-status-update)
- [Midtrans PHP SDK](https://github.com/Midtrans/midtrans-php)
- [Midtrans Sandbox Test Credentials](https://docs.midtrans.com/reference/sandbox-test)
- Arsitektur project: `docs/architecture.md`
- ADR decisions: `docs/decisions.md`

---

*Dokumen ini dibuat berdasarkan analisis repository `johnd-creator/kojaya` pada Juni 2026.*
*Simpan file ini di `docs/midtrans_api_plan.md` agar terbaca oleh Claude Code dan AI assistant lainnya.*
