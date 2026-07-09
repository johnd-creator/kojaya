# Rencana Implementasi Keranjang Member untuk Kopi dan POS

Tanggal: 2026-07-09
Repo acuan:

- Backend: `johnd-creator/kojaya`
- Android: `johnd-creator/KojayaApp`

## 1. Latar Belakang

User membutuhkan POS tidak hanya untuk admin/kasir, tetapi juga dapat digunakan oleh role anggota/member dari aplikasi Android. Anggota harus bisa memilih produk toko koperasi, memasukkan beberapa produk ke keranjang, lalu checkout dari aplikasi.

Untuk pemesanan kopi, masalah utama saat ini adalah flow terasa single-order. Anggota bisa pesan banyak, tetapi harus membuat pesanan satu per satu sehingga tidak efisien untuk pesanan kolektif atau jumlah besar. Solusi yang direncanakan adalah menambahkan pola **cart/keranjang** pada flow kopi dan flow toko koperasi.

Catatan istilah: di dokumen ini digunakan istilah **cart/keranjang**, bukan `chart`.

## 2. Temuan Kondisi Saat Ini

### 2.1 Android `KojayaApp`

- Aplikasi Android menggunakan satu module `:app` dengan namespace `com.kojaya.kojaya`.
- Build config sudah diarahkan ke backend API `https://kojaya.ubpbsla.com/api/` dan app variant `member`.
- `ServiceLocator` sudah mendaftarkan `CoffeeApi`, `MemberApi`, dan `AdminPosApi`.
- Route member sudah memiliki flow kopi:
  - `member/coffee`
  - `member/coffee/orders/{orderId}`
- Route admin sudah memiliki flow POS:
  - `admin/pos`
  - `admin/pos/cart`
  - `admin/pos/return`
  - `admin/pos/success`
- Repository member Android sudah punya fungsi:
  - `coffeeMenu()`
  - `createCoffeeOrder(req)`
  - `coffeeOrder(id)`
- DTO Android `CreateCoffeeOrderRequest` saat ini masih single item:
  - `posProductId`
  - `quantity`
  - `sugarLevel`
  - `iceLevel`
  - `cupSize`
  - `paymentMethod`
  - `channel`

Kesimpulan Android: backend sudah lebih siap untuk multi item kopi, tetapi Android belum expose model cart/multi item.

### 2.2 Backend `kojaya`

Backend API sudah memiliki beberapa titik penting:

- Route member kopi:
  - `GET /api/v1/member/coffee/menu`
  - `POST /api/v1/member/coffee/orders`
  - `GET /api/v1/member/coffee/orders/{coffeeOrder}`
- Route POS existing:
  - `GET /api/v1/pos/products`
  - `POST /api/v1/pos/transactions`
  - `POST /api/v1/pos/returns`
- `MemberCoffeeOrderController::store()` sudah membuat `MemberPaymentIntent` untuk `coffee_order`.
- `StoreMemberCoffeeOrderRequest` sudah mendukung payload `items[]`:
  - `items.*.pos_product_id`
  - `items.*.quantity`
  - `items.*.sugar_level`
  - `items.*.ice_level`
  - `items.*.cup_size`
- `MemberPaymentSettlementService::settleCoffeeOrder()` sudah mengambil metadata `items`, lalu membuat POS transaction setelah payment intent berstatus `PAID`.
- `PosTransactionService::create()` sudah mendukung multi item, validasi stok, idempotency via `client_reference`, posting jurnal, poin anggota, notifikasi, dan pembayaran `CASH`, `TRANSFER`, `QRIS`, `MEMBER_CREDIT`.
- `PosApiController` existing masih guard untuk admin/kasir POS, karena memakai `ability:pos:read/write` dan `can('access_cooperative_pos')`.

Kesimpulan backend: untuk kopi, fondasi multi item sudah ada. Untuk toko koperasi/member POS, perlu endpoint member-facing baru agar tidak membuka endpoint kasir/admin langsung ke anggota.

## 3. Prinsip Desain

1. **Jangan buka endpoint admin POS langsung ke member.**
   Endpoint admin POS tetap untuk kasir/admin. Member harus memakai endpoint khusus member store supaya permission, data exposure, dan UX lebih aman.

2. **Harga dan stok selalu server-authoritative.**
   Android boleh menampilkan estimasi subtotal, tetapi checkout backend wajib hitung ulang harga, stok, diskon, dan total.

3. **Cart Android bersifat local UI state.**
   Untuk MVP, cart tidak perlu disimpan di server sebelum checkout. Server baru menerima items saat checkout.

4. **Checkout harus idempotent.**
   Android wajib mengirim `client_reference` unik agar retry tidak membuat transaksi dobel.

5. **Produk kopi dan produk toko boleh memakai POS product yang sama, tetapi flow order berbeda.**
   Kopi membutuhkan customization dan kitchen/pickup status. Toko koperasi membutuhkan cart katalog umum dan pembayaran/ambil barang.

6. **Stok dikurangi setelah pembayaran berhasil untuk flow payment intent.**
   Ini mengikuti pola kopi existing. Untuk fase berikutnya bisa ditambah reservation stock dengan expiry.

## 4. Target UX Member

### 4.1 Flow Kopi dengan Keranjang

1. Anggota buka menu kopi.
2. Anggota pilih produk kopi.
3. App tampilkan bottom sheet/detail customization:
   - sugar level
   - ice level
   - cup size
   - quantity
4. Tombol: `Tambah ke Keranjang`.
5. Di menu kopi muncul sticky mini cart:
   - jumlah item/cup
   - subtotal estimasi
   - tombol `Lihat Keranjang`
6. Halaman keranjang kopi:
   - daftar item
   - edit qty
   - edit opsi per item
   - hapus item
   - subtotal
   - pilih channel pembayaran
7. Checkout membuat payment intent.
8. Setelah checkout sukses:
   - tampilkan QRIS/payment instruction bila ada `charge`
   - status: `Menunggu Pembayaran`
9. Setelah pembayaran settled, backend membuat POS transaction dan CoffeeOrder.
10. Anggota melihat detail/progress order:
    - diterima
    - sedang dibuat
    - siap diambil
    - selesai

### 4.2 Flow Toko Koperasi / POS Member

1. Anggota buka menu `Toko Koperasi`.
2. App menampilkan katalog produk POS yang boleh dibeli member.
3. Anggota search/filter kategori.
4. Anggota tambah banyak produk ke keranjang.
5. Halaman keranjang toko:
   - item
   - qty
   - subtotal estimasi
   - pilihan pembayaran
   - catatan pengambilan bila dibutuhkan
6. Checkout membuat `MemberPaymentIntent` khusus belanja POS.
7. Setelah pembayaran berhasil, backend membuat `PosTransaction`.
8. Anggota dapat melihat transaksi di riwayat/unified transaction.
9. Untuk MVP, fulfillment bisa berupa `Ambil di koperasi/kantin`.

## 5. Rencana Backend

### 5.1 Kopi: Optimasi Existing Endpoint

Backend kopi tidak perlu dibuat ulang. Endpoint `POST /api/v1/member/coffee/orders` sudah mendukung `items[]`. Yang perlu dipastikan:

- response pending payment tetap membawa `items` lengkap;
- `client_reference` dari Android disimpan ke metadata intent;
- settlement tetap membuat satu `CoffeeOrder` untuk satu checkout cart;
- `CoffeeOrder.customization.items` menyimpan semua item dan opsi customization;
- response detail order menampilkan `items` dari POS transaction dan customization dari CoffeeOrder.

#### Payload checkout kopi yang ditargetkan

```json
{
  "client_reference": "ANDROID-COFFEE-20260709-UUID",
  "channel": "QRIS",
  "items": [
    {
      "pos_product_id": 1,
      "quantity": 3,
      "sugar_level": "Normal",
      "ice_level": "Less Ice",
      "cup_size": "Reguler"
    },
    {
      "pos_product_id": 2,
      "quantity": 2,
      "sugar_level": "No Sugar",
      "ice_level": "Warm",
      "cup_size": "Large"
    }
  ]
}
```

### 5.2 Tambah Endpoint Member Store / POS Member

Jangan expose `GET /api/v1/pos/products` dan `POST /api/v1/pos/transactions` existing langsung ke anggota, karena route tersebut untuk kasir/admin POS.

Tambahkan controller baru, contoh:

- `App\Http\Controllers\Api\V1\MemberStoreController`
- `App\Http\Requests\Api\StoreMemberStoreOrderRequest`

Route baru di dalam prefix `v1/member`:

```php
Route::get('/store/catalog', [MemberStoreController::class, 'catalog'])
    ->middleware('ability:member:read');

Route::post('/store/orders', [MemberStoreController::class, 'store'])
    ->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);

Route::get('/store/orders/{intentOrTransaction}', [MemberStoreController::class, 'show'])
    ->middleware('ability:member:read');
```

#### Catalog response

Gunakan `PosProduct::sellable()` dan filter produk yang boleh muncul untuk member. Untuk MVP, bisa pakai semua produk POS yang `is_active = true` dan `is_discontinued = false`, kecuali nanti ditambahkan flag khusus.

Rekomendasi field response:

```json
{
  "data": {
    "categories": ["Semua", "Sembako", "Minuman", "Snack"],
    "items": [
      {
        "id": "10",
        "sku": "SKU-001",
        "name": "Produk A",
        "description": "Brand Variant",
        "price": 15000,
        "category": "Sembako",
        "stock": 20,
        "image_url": "https://..."
      }
    ]
  }
}
```

#### Checkout POS member request

```json
{
  "client_reference": "ANDROID-STORE-20260709-UUID",
  "channel": "QRIS",
  "fulfillment_method": "PICKUP",
  "pickup_location": "Koperasi/Kantin Kojaya",
  "notes": "Diambil setelah jam kerja",
  "items": [
    {
      "pos_product_id": 10,
      "quantity": 2
    },
    {
      "pos_product_id": 11,
      "quantity": 1
    }
  ]
}
```

#### Checkout POS member behavior

- Resolve anggota aktif dari user login, sama seperti flow kopi.
- Validasi item min 1 dan max item cart misalnya 50.
- Validasi quantity per item, misalnya max 999 atau mengikuti rule POS existing.
- Ambil harga dan stok dari `pos_products`, jangan percaya subtotal dari app.
- Buat `MemberPaymentIntent` baru dengan payable type baru:
  - `PAYABLE_POS_ORDER = 'pos_order'`
- Simpan metadata:
  - `client_reference`
  - `items`
  - `fulfillment_method`
  - `pickup_location`
  - `notes`
- Return payment intent + charge sama seperti kopi.

### 5.3 Tambah Settlement untuk POS Member

Tambahkan branch baru di `MemberPaymentSettlementService::settle()`:

```php
MemberPaymentIntent::PAYABLE_POS_ORDER => $this->settlePosOrder($intent),
```

`settlePosOrder()` melakukan:

1. Ambil metadata `items`.
2. Validasi item masih tersedia.
3. Panggil `PosTransactionService::create()` dengan:
   - `client_reference`
   - `cooperative_member_id`
   - `payment_method` = channel intent, misalnya QRIS
   - `amount` = amount intent
   - `cash_received` = amount intent
   - `discount_amount` = 0
   - `items`
4. Update intent `payable_id` ke `pos_transaction_id` bila belum ada.
5. Dispatch notifikasi ke member.
6. Return `pos_order:{transaction_id}`.

### 5.4 Opsi Kredit Anggota

Untuk MVP, rekomendasi payment method member app:

- QRIS dulu.
- VA/E-Wallet/Transfer mengikuti gateway bila sudah siap.
- `MEMBER_CREDIT` jangan dibuka dulu ke Android sampai aturan limit, approval, dan repayment jelas.

Kalau nanti mau buka `MEMBER_CREDIT`, backend sudah punya dasar di `PosTransactionService`, tetapi perlu endpoint member khusus yang memvalidasi limit, status anggota, dan policy koperasi.

### 5.5 Data Model Tambahan Opsional

Untuk MVP, `MemberPaymentIntent` + `PosTransaction` cukup.

Untuk fase lebih rapi, tambahkan model order khusus:

- `MemberStoreOrder`
- `MemberStoreOrderItem`

Kapan perlu model ini?

- butuh status fulfillment terpisah dari transaksi;
- perlu admin queue `menunggu diambil`, `diproses`, `selesai`;
- perlu cancel/refund sebelum pickup;
- perlu reservasi stok sebelum payment settled.

MVP bisa menunda model ini agar scope lebih kecil.

## 6. Rencana Android

### 6.1 Kopi: Tambah Cart State

Tambahkan domain/UI model:

```kotlin
data class CoffeeCartItem(
    val product: CoffeeProduct,
    val quantity: Int,
    val sugarLevel: String,
    val iceLevel: String,
    val cupSize: String,
)
```

Cart merge key:

```text
productId + sugarLevel + iceLevel + cupSize
```

Artinya produk yang sama tetapi opsi berbeda tetap menjadi line berbeda.

Update DTO Android:

```kotlin
data class CreateCoffeeOrderRequest(
    @SerializedName("pos_product_id") val posProductId: Int? = null,
    val quantity: Int? = null,
    val items: List<CreateCoffeeOrderItemRequest>? = null,
    @SerializedName("payment_method") val paymentMethod: String? = null,
    val channel: String? = null,
    @SerializedName("client_reference") val clientReference: String? = null,
)

data class CreateCoffeeOrderItemRequest(
    @SerializedName("pos_product_id") val posProductId: Int,
    val quantity: Int,
    @SerializedName("sugar_level") val sugarLevel: String? = null,
    @SerializedName("ice_level") val iceLevel: String? = null,
    @SerializedName("cup_size") val cupSize: String? = null,
)
```

### 6.2 Kopi: Screen Baru

Tambahkan route Android:

- `member/coffee/cart`
- opsional `member/coffee/checkout`
- existing `member/coffee/orders/{orderId}` tetap dipakai untuk order settled

Update UX:

- `CoffeeMenuScreen`:
  - produk card punya tombol `Tambah`;
  - klik produk membuka customization bottom sheet;
  - sticky mini cart muncul saat item > 0.
- `CoffeeCartScreen`:
  - list item;
  - edit qty;
  - hapus item;
  - subtotal;
  - pilih channel;
  - checkout.
- `CoffeeCheckout/PendingPaymentScreen`:
  - tampilkan QRIS/instruksi payment dari `charge`;
  - status `Menunggu Pembayaran`.

Catatan penting: response backend untuk pending payment mengembalikan `id` dengan format `intent:{id}`. Jangan langsung memanggil `GET /coffee/orders/{intent:id}` karena route detail order existing menunggu `CoffeeOrder`, bukan payment intent. Android perlu menangani pending payment sebagai state tersendiri sampai payment settled.

### 6.3 POS Member: Tambah API dan Repository

Tambahkan API Android baru, contoh:

```kotlin
interface MemberStoreApi {
    @GET("v1/member/store/catalog")
    suspend fun catalog(
        @Query("search") search: String? = null,
        @Query("category") category: String? = null,
    ): Response<DataWrapper<MemberStoreCatalogDto>>

    @POST("v1/member/store/orders")
    suspend fun createOrder(
        @Body body: CreateMemberStoreOrderRequest,
    ): Response<DataWrapper<MemberStoreOrderDto>>
}
```

Daftarkan di `ServiceLocator` dan expose lewat `MemberRepository`.

### 6.4 POS Member: UI Flow

Tambahkan route:

- `member/store`
- `member/store/cart`
- `member/store/orders/{orderId}` atau `member/store/payment-intents/{intentId}`

Tambahkan entry dari dashboard/member menu:

- title: `Toko Koperasi`
- subtitle: `Belanja kebutuhan anggota dari aplikasi`
- badge opsional: `Baru`

Cart merge key POS umum:

```text
productId
```

Screen:

- `MemberStoreCatalogScreen`
- `MemberStoreCartScreen`
- `MemberStorePendingPaymentScreen`
- `MemberStoreOrderDetailScreen` bila backend sudah punya status order/transaction detail

## 7. Rekomendasi Tahapan Implementasi

### Phase 1 — Kopi Multi Item di Android

Scope paling kecil dan paling cepat terasa manfaatnya.

Backend:

- Tidak perlu endpoint baru.
- Review response pending payment dan detail order.
- Tambahkan test untuk `POST /member/coffee/orders` dengan `items[]` dua produk.

Android:

- Update DTO `CreateCoffeeOrderRequest` agar support `items`.
- Tambah `CoffeeCartItem` dan cart state.
- Tambah `CoffeeCartScreen`.
- Update `CoffeeMenuScreen` supaya add-to-cart, bukan langsung order.
- Handle pending payment intent tanpa memanggil detail order menggunakan `intent:{id}`.

Acceptance criteria:

- Anggota bisa pesan 2-5 jenis kopi dalam satu checkout.
- Subtotal di Android sesuai total intent backend.
- Checkout menghasilkan QRIS/payment instruction.
- Setelah payment settled, order muncul sebagai satu CoffeeOrder dengan semua items di detail.

### Phase 2 — Backend Member Store

Backend:

- Tambah `MemberStoreController`.
- Tambah `StoreMemberStoreOrderRequest`.
- Tambah constant `MemberPaymentIntent::PAYABLE_POS_ORDER`.
- Tambah `settlePosOrder()` di settlement service.
- Tambah route member store.
- Tambah feature tests.

Acceptance criteria:

- Member bisa melihat katalog produk sellable.
- Member tidak perlu ability `pos:read`/`pos:write`.
- Member hanya bisa checkout atas nama dirinya sendiri.
- Payment intent tercipta dengan amount hasil hitung backend.
- Setelah webhook `PAID`, POS transaction tercipta dan stok berkurang.

### Phase 3 — Android Member Store

Android:

- Tambah `MemberStoreApi`.
- Register ke `ServiceLocator`.
- Tambah repository methods.
- Tambah route dan screen catalog/cart/pending payment.
- Tambah entry dashboard/menu.

Acceptance criteria:

- Anggota bisa tambah banyak produk ke keranjang.
- Quantity bisa diedit sebelum checkout.
- Checkout mengirim `items[]` dan `client_reference`.
- Pending payment ditampilkan jelas.

### Phase 4 — Fulfillment dan Operasional Admin

Bila koperasi butuh antrean pengambilan barang:

- Tambah model `MemberStoreOrder`.
- Tambah admin queue:
  - pesanan baru;
  - disiapkan;
  - siap diambil;
  - selesai;
  - batal/refund.
- Tambah push notification status order.

## 8. Edge Case yang Harus Dijaga

1. **Stok berubah saat checkout.**
   Backend harus menolak jika stok tidak cukup saat payment settlement atau checkout.

2. **Harga berubah antara catalog dan checkout.**
   Backend tetap hitung ulang. Android tampilkan pesan jika total berubah.

3. **Retry checkout karena jaringan putus.**
   Gunakan `client_reference` agar tidak dobel.

4. **Payment intent paid tetapi settlement gagal karena stok habis.**
   Butuh status manual review/refund. Untuk MVP, minimal log error dan admin alert.

5. **Item kopi dengan opsi berbeda.**
   Jangan merge jika sugar/ice/cup beda.

6. **Pending intent belum menjadi CoffeeOrder.**
   Android jangan paksa buka order detail settled.

7. **Member tidak aktif.**
   Backend harus menolak checkout.

8. **Produk discontinued/non-active.**
   Jangan muncul di catalog dan jangan bisa checkout.

## 9. Test Checklist

### Backend Feature Tests

- `member_can_create_coffee_order_with_multiple_items`
- `member_coffee_order_rejects_unavailable_product`
- `member_coffee_order_is_idempotent_by_client_reference`
- `member_store_catalog_returns_sellable_products`
- `member_store_order_creates_payment_intent`
- `member_store_order_recalculates_total_server_side`
- `member_store_order_settlement_creates_pos_transaction`
- `member_store_order_rejects_inactive_member`
- `member_cannot_access_admin_pos_endpoint_without_pos_ability`

### Android Unit/UI Tests

- Coffee cart adds item.
- Coffee cart merges same product and same customization.
- Coffee cart keeps separate rows for different customization.
- Coffee cart checkout maps to `items[]` payload.
- Store cart adds/removes/updates quantity.
- Store checkout creates stable `client_reference` per attempt.
- Pending payment screen handles `intent:{id}` safely.

## 10. Keputusan MVP yang Direkomendasikan

1. Mulai dari kopi multi item di Android karena backend sudah mendukung `items[]`.
2. Jangan buka endpoint admin POS untuk member.
3. Buat endpoint member store khusus di backend.
4. Gunakan `MemberPaymentIntent` untuk checkout POS member.
5. Settlement membuat `PosTransaction` setelah payment `PAID`.
6. Tunda model fulfillment khusus sampai flow belanja dasar sudah stabil.

## 11. Urutan Prompt untuk Codex

### Prompt 1 — Android Coffee Cart

```text
Implement multi-item coffee cart in KojayaApp. Read backend contract in docs/member-pos-cart-plan.md. Update CoffeeApi DTO to support items[] and client_reference while preserving legacy single item compatibility. Add CoffeeCartItem state, cart screen, and update CoffeeMenuScreen so products are added to cart with customization instead of creating immediate order. Ensure checkout sends POST v1/member/coffee/orders with items[]. Do not call GET coffee order detail for id values prefixed with intent:. Add unit tests for payload mapping and cart merge behavior.
```

### Prompt 2 — Backend Member Store

```text
Implement member-facing store/POS checkout in kojaya backend. Add MemberStoreController, StoreMemberStoreOrderRequest, routes under v1/member/store, MemberPaymentIntent::PAYABLE_POS_ORDER, and settlement handling that creates PosTransaction via PosTransactionService after gateway PAID. Do not expose existing admin POS endpoints to member. Add feature tests for catalog, checkout, permission boundaries, server-side total calculation, and settlement.
```

### Prompt 3 — Android Member Store

```text
Implement Toko Koperasi member shopping flow in KojayaApp. Add MemberStoreApi, repository methods, catalog/cart/pending payment screens, routes member/store and member/store/cart, and dashboard entry. Cart must support multiple POS products, local subtotal estimate, stable client_reference, and checkout to POST v1/member/store/orders. Add tests for cart state and request mapping.
```
