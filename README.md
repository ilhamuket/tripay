# Tripay PHP & Laravel SDK: Library Payment Gateway Tripay (QRIS, Virtual Account, E-Wallet)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ilhamuket/tripay.svg?style=flat-square&label=packagist)](https://packagist.org/packages/ilhamuket/tripay)
[![Total Downloads](https://img.shields.io/packagist/dt/ilhamuket/tripay.svg?style=flat-square)](https://packagist.org/packages/ilhamuket/tripay)
[![npm](https://img.shields.io/npm/v/@uket/tripay.svg?style=flat-square&label=npm)](https://www.npmjs.com/package/@uket/tripay)
[![CI](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml/badge.svg)](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/ilhamuket/tripay.svg?style=flat-square)](LICENSE.md)

**Library Tripay untuk PHP dan Laravel** (juga tersedia untuk [Node.js / NestJS](#nodejs--nestjs)) untuk integrasi
**[Tripay Payment Gateway](https://tripay.co.id)**: buat transaksi QRIS, Virtual Account (BRIVA, BNIVA, BCAVA, Mandiri VA, dll.),
e-wallet (OVO, DANA, ShopeePay), dan gerai retail (Alfamart, Indomaret). Fitur lain: verifikasi callback, cek status pembayaran,
kalkulator biaya, instruksi pembayaran, dan Open Payment. Signature Tripay dibuat otomatis.

Dokumentasi ini berisi **contoh kode integrasi Tripay yang lengkap dan siap pakai**: instalasi, konfigurasi sandbox dan production,
membuat transaksi, menangani callback, sampai troubleshooting error yang sering muncul.

> ℹ️ Ini library **komunitas (tidak resmi)** dan tidak berafiliasi dengan PT Trijaya Digital Grup (Tripay).
> Dokumentasi API resmi: [tripay.co.id/developer](https://tripay.co.id/developer).

```bash
composer require ilhamuket/tripay
```

---

## Daftar Isi

- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi Sandbox & Production](#konfigurasi-sandbox--production)
- [Quick Start: Membuat Transaksi Tripay](#quick-start-membuat-transaksi-tripay)
- [Penggunaan Lengkap](#penggunaan-lengkap)
  - [Daftar Channel Pembayaran](#daftar-channel-pembayaran)
  - [Kalkulator Biaya (Fee)](#kalkulator-biaya-fee)
  - [Membuat Transaksi (Closed Payment)](#membuat-transaksi-closed-payment)
  - [Detail & Cek Status Transaksi](#detail--cek-status-transaksi)
  - [Daftar Transaksi](#daftar-transaksi)
  - [Instruksi Pembayaran](#instruksi-pembayaran)
  - [Open Payment](#open-payment)
- [Callback Tripay (Webhook)](#callback-tripay-webhook)
- [Kode Metode Pembayaran](#kode-metode-pembayaran)
- [Signature Tripay](#signature-tripay)
- [Tanpa Laravel (PHP Native)](#tanpa-laravel-php-native)
- [Node.js / NestJS](#nodejs--nestjs)
- [Referensi API](#referensi-api)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [English Summary](#english-summary)
- [Changelog](#changelog) · [Kontribusi](#kontribusi) · [Lisensi](#lisensi)

---

## Fitur

- ✅ **Laravel 10, 11, 12**: service provider, facade `Tripay`, config, auto-discovery
- ✅ **PHP 8.2+**, bisa dipakai tanpa Laravel
- ✅ **Closed Payment**: QRIS, Virtual Account, e-wallet, retail
- ✅ **Open Payment**: kode bayar / QRIS yang bisa dibayar berulang (khusus production)
- ✅ **Callback / webhook**: validasi `X-Callback-Signature` (constant-time) dan parsing data
- ✅ Daftar channel, kalkulator biaya, detail transaksi, cek status, daftar transaksi, instruksi pembayaran
- ✅ **Signature Tripay otomatis** untuk transaksi dan Open Payment
- ✅ Builder `TransactionData` / `OrderItem` dengan validasi sebelum request
- ✅ Response object bertipe (`isPaid()`, `getCheckoutUrl()`, `getPayCode()`, …)
- ✅ **Aman**: verifikasi TLS selalu aktif, status `UNPAID` tidak pernah terbaca lunas
- ✅ Diuji di CI (PHP 8.2 & 8.3) dan langsung ke sandbox Tripay

## Persyaratan

| Komponen | Versi |
|---|---|
| PHP | 8.2 atau lebih baru |
| Laravel (opsional) | 10, 11, atau 12 |
| Ekstensi | `json`, `curl` (lewat Guzzle 7) |
| Akun Tripay | merchant sandbox (menu **Simulator**) atau production |

## Instalasi

```bash
composer require ilhamuket/tripay
```

Laravel otomatis mendaftarkan service provider dan facade. Publish file config (opsional):

```bash
php artisan vendor:publish --tag=tripay-config
```

## Konfigurasi Sandbox & Production

Tambahkan ke `.env`. Untuk sandbox, ambil kredensial dari dashboard Tripay → **Simulator** → Merchant.
Untuk production, ambil dari menu **Merchant** → API Key.

```env
TRIPAY_MODE=sandbox            # sandbox | production
TRIPAY_MERCHANT_CODE=T0001
TRIPAY_API_KEY=DEV-xxxxxxxxxxxxxxxx
TRIPAY_PRIVATE_KEY=xxxxx-xxxxx-xxxxx-xxxxx-xxxxx

# Opsional: path CA bundle bila muncul "cURL error 60/77: SSL certificate problem"
# TRIPAY_CA_BUNDLE=/path/to/cacert.pem
```

| Mode | Base URL API |
|---|---|
| `sandbox` | `https://tripay.co.id/api-sandbox` |
| `production` | `https://tripay.co.id/api` |

> 🔐 Jangan commit `.env` atau menaruh private key di kode frontend. Private key dipakai untuk signature dan verifikasi callback.

## Quick Start: Membuat Transaksi Tripay

```php
use Ilhamuket\Tripay\Facades\Tripay;
use Ilhamuket\Tripay\Data\TransactionData;
use Ilhamuket\Tripay\PaymentMethod;

$transaction = (new TransactionData())
    ->setMethod(PaymentMethod::QRIS)
    ->setMerchantRef('INV-' . time())          // nomor invoice di sistem kamu
    ->setAmount(100000)                        // total dalam rupiah
    ->setCustomerName('Budi Santoso')
    ->setCustomerEmail('budi@example.com')
    ->setCustomerPhone('081234567890')
    ->addOrderItem(['name' => 'Kaos Polos', 'price' => 50000, 'quantity' => 2])
    ->setCallbackUrl(route('tripay.callback'))
    ->setReturnUrl(route('orders.show', 'INV-001'))
    ->setExpiryHours(24);

$response = Tripay::createTransaction($transaction);

$response->getReference();   // referensi Tripay, mis. DEV-T0001000012345ABCDE
$response->getCheckoutUrl(); // halaman pembayaran Tripay (bisa langsung redirect)
$response->getQrUrl();       // gambar QR (QRIS)
$response->getPayCode();     // nomor VA / kode bayar (Virtual Account, retail)
```

Setelah pelanggan membayar, Tripay mengirim [callback](#callback-tripay-webhook) ke `callback_url`. Di sanalah status order ditandai lunas.

## Penggunaan Lengkap

Semua contoh memakai facade `Tripay`. Bisa juga lewat dependency injection:

```php
use Ilhamuket\Tripay\Tripay;

class CheckoutController extends Controller
{
    public function __construct(private Tripay $tripay) {}

    public function channels()
    {
        return $this->tripay->getPaymentChannels()->getActiveChannels();
    }
}
```

### Daftar Channel Pembayaran

```php
$channels = Tripay::getPaymentChannels();

foreach ($channels->getActiveChannels() as $channel) {
    echo $channel->getCode() . ' - ' . $channel->getName();   // QRIS - QRIS by ShopeePay
    $channel->getIconUrl();
    $channel->getMinimumAmount();
    $channel->calculateFee(100000);
}

$channels->getVirtualAccounts();     // hanya VA
$channels->getEWallets();            // hanya e-wallet
$channels->getChannel('BRIVA');      // satu channel
Tripay::getPaymentChannels('QRIS');  // filter dari API
```

### Kalkulator Biaya (Fee)

```php
$fee = Tripay::calculateFee(100000, 'QRIS');

$fee->getMerchantFee('QRIS');  // biaya yang ditanggung merchant
$fee->getCustomerFee('QRIS');  // biaya yang ditanggung pelanggan
$fee->getFees();               // semua channel bila kode tidak diisi
```

### Membuat Transaksi (Closed Payment)

Closed payment adalah transaksi sekali bayar dengan nominal tetap. Field wajib: `method`, `merchant_ref`, `amount`,
`customer_name`, `customer_email`, dan minimal satu `order_items`.

```php
use Ilhamuket\Tripay\Data\OrderItem;

$transaction = TransactionData::make([
    'method'         => PaymentMethod::BRIVA,
    'merchant_ref'   => 'INV-2026-0001',
    'amount'         => 150000,
    'customer_name'  => 'Budi Santoso',
    'customer_email' => 'budi@example.com',
    'customer_phone' => '081234567890',   // wajib untuk beberapa channel (mis. OVO)
    'callback_url'   => 'https://tokokamu.com/api/callback/tripay',
    'return_url'     => 'https://tokokamu.com/orders/INV-2026-0001',
    'expired_time'   => time() + 2 * 60 * 60,   // unix timestamp (detik)
]);

$transaction->addOrderItem(
    OrderItem::make([
        'sku'         => 'SKU-001',
        'name'        => 'Sepatu Lari',
        'price'       => 150000,
        'quantity'    => 1,
        'product_url' => 'https://tokokamu.com/p/sepatu-lari',
        'image_url'   => 'https://tokokamu.com/img/sepatu-lari.jpg',
    ])
);

$response = Tripay::createTransaction($transaction);

if ($response->isVirtualAccount()) {
    echo 'Transfer ke VA ' . $response->getPayCode();
}
$response->getAmount();          // nominal yang dibayar pelanggan
$response->getAmountReceived();  // nominal diterima merchant setelah fee
$response->getExpiredAt();       // DateTimeInterface
$response->getInstructions();    // langkah pembayaran
```

> ⏱️ Batas `expired_time` berbeda per channel (contoh di dokumentasi Tripay: QRIS 10–60 menit, BNIVA 15–1.440 menit).
> Bila tidak diisi, Tripay memakai default 24 jam.

### Detail & Cek Status Transaksi

```php
$detail = Tripay::getTransactionDetail('DEV-T0001000012345ABCDE');

$detail->getStatus();          // UNPAID | PAID | EXPIRED | FAILED | REFUND
$detail->isPaid();
$detail->getPaidAtDateTime();

$status = Tripay::checkTransactionStatus('DEV-T0001000012345ABCDE');
$status->isPaid();             // membaca pesan "PAID" maupun "DIBAYAR"
$status->isUnpaid();
$status->getStatusMessage();   // mis. "Status transaksi saat ini DIBAYAR"
```

> Untuk menandai order lunas, utamakan **callback** atau `getTransactionDetail()->isPaid()`.
> `checkTransactionStatus()` hanya mengembalikan pesan teks.

### Daftar Transaksi

```php
$list = Tripay::getTransactions([
    'page'     => 1,
    'per_page' => 50,          // maks 50
    'sort'     => 'desc',
    'status'   => 'PAID',      // opsional: reference, merchant_ref, method
]);

foreach ($list->getTransactions() as $trx) {
    echo $trx['reference'] . ' ' . $trx['status'];
}
$list->getTotalRecords();
$list->hasMorePages();
$list->getNextPage();
```

### Instruksi Pembayaran

```php
$instruction = Tripay::getPaymentInstruction(
    code: 'BRIVA',
    payCode: '1234567890123456',   // opsional, mengisi nomor VA di teks instruksi
    amount: 150000,                // opsional
    allowHtml: false               // default true
);

foreach ($instruction->getInstructions() as $item) {
    echo $item['title'];           // mis. "Internet Banking"
    print_r($item['steps']);
}
```

### Open Payment

Open Payment adalah kode bayar (VA) atau QRIS **yang bisa dibayar berulang kali** dengan nominal bebas, misalnya untuk top-up saldo
member atau donasi. Endpoint ini **hanya tersedia di production**. Di mode sandbox SDK langsung melempar `TripayApiException`
dengan pesan yang jelas.

```php
// TRIPAY_MODE=production
$op = Tripay::createOpenPayment(
    method: 'BCAVA',             // atau 'QRIS'
    merchantRef: 'MEMBER-0042',  // opsional
    customerName: 'Budi'         // opsional
);

$uuid = $op->getUuid();          // simpan untuk cek detail / riwayat
$op->getPayCode();               // nomor VA (null untuk QRIS)
$op->getQrString();              // isi QRIS (null untuk VA)

$detail = Tripay::getOpenPaymentDetail($uuid);

$history = Tripay::getOpenPaymentTransactions($uuid, [
    'start_date' => '2026-11-01 00:00:00',   // format Y-m-d H:i:s
    'end_date'   => '2026-11-30 23:59:59',
    'per_page'   => 50,                      // maks 100
]);
foreach ($history->getTransactions() as $payment) {
    // $payment['reference'], $payment['amount'], $payment['status'], $payment['paid_at']
}
```

| | Closed Payment | Open Payment |
|---|---|---|
| Nominal | Tetap, ditentukan merchant | Bebas, ditentukan pembayar |
| Berapa kali dibayar | Sekali | Berulang kali |
| Kedaluwarsa | Ya (`expired_time`) | Tidak |
| Sandbox | ✅ | ❌ (hanya production) |
| Signature | `merchantCode + merchantRef + amount` | `merchantCode + method + merchantRef` |

## Callback Tripay (Webhook)

Tripay mengirim `POST` JSON ke `callback_url` setiap kali status pembayaran berubah (PAID, EXPIRED, FAILED, REFUND).
Header pentingnya `X-Callback-Signature` (HMAC-SHA256 dari raw body) dan `X-Callback-Event: payment_status`.

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ilhamuket\Tripay\Facades\Tripay;

class TripayCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $json = $request->getContent();                          // RAW body, jangan json_encode ulang
        $signature = (string) $request->header('X-Callback-Signature');

        if (! Tripay::validateCallback($signature, $json)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json(['success' => false, 'message' => 'Unrecognized event'], 400);
        }

        $callback = Tripay::parseCallback($json);

        $order = Order::where('invoice', $callback->getMerchantRef())->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Idempotent: callback bisa dikirim ulang, jangan proses order yang sudah final
        if ($order->status === 'paid') {
            return response()->json(['success' => true]);
        }

        if ($callback->isPaid()) {
            $order->update(['status' => 'paid', 'paid_at' => $callback->getPaidAtDateTime()]);
        } elseif ($callback->isExpired() || $callback->isFailed()) {
            $order->update(['status' => strtolower($callback->getStatus())]);
        }

        return response()->json(['success' => true]);            // wajib, bila tidak Tripay mengirim ulang
    }
}
```

Route di `routes/api.php` (tanpa CSRF):

```php
Route::post('/callback/tripay', \App\Http\Controllers\TripayCallbackController::class)->name('tripay.callback');
```

Kalau route ditaruh di `routes/web.php`, kecualikan dari CSRF:

```php
// Laravel 11 / 12: bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['callback/tripay']);
})

// Laravel 10: app/Http/Middleware/VerifyCsrfToken.php
protected $except = ['callback/tripay'];
```

Checklist callback production:

- Balas `{"success": true}`. Jika tidak, Tripay mengirim ulang (jeda 2 menit, maksimal 3 kali).
- Verifikasi signature dari **raw body**, bukan hasil `json_encode($request->all())`.
- Proses secara **idempotent** dan cocokkan nominal dengan order.
- Payload Open Payment ditandai `is_closed_payment = 0` (`$callback->isClosedPayment() === false`).
- Uji dengan **Callback Tester** di dashboard Tripay (sandbox: menu Simulator → Callback).
- Bila server memakai whitelist IP, lihat daftar IP callback di [dokumentasi Tripay](https://tripay.co.id/developer).

## Kode Metode Pembayaran

Konstanta tersedia di `Ilhamuket\Tripay\PaymentMethod`. Channel yang aktif bisa berbeda per merchant; aktifkan di dashboard Tripay → Merchant → Channel Pembayaran.

| Kategori | Kode |
|---|---|
| Virtual Account | `BRIVA` (BRI), `BNIVA` (BNI), `BCAVA` (BCA), `MANDIRIVA` (Mandiri), `PERMATAVA` (Permata), `BSIVA` (BSI), `CIMBVA` (CIMB Niaga), `MYBVA` (Maybank), `MUAMALATVA` (Muamalat), `SMSVA` (Sinarmas), `OCBCVA` (OCBC NISP), `SAMPOERNAVA` (Sahabat Sampoerna) |
| QRIS | `QRIS`, `QRISC`, `QRIS2` |
| E-Wallet | `OVO`, `DANA`, `SHOPEEPAY` |
| Retail | `ALFAMART`, `INDOMARET`, `ALFAMIDI` |

```php
PaymentMethod::virtualAccounts();          // array kode VA
PaymentMethod::eWallets();
PaymentMethod::convenienceStores();
PaymentMethod::isVirtualAccount('BRIVA');  // true
PaymentMethod::isQris('QRISC');            // true
```

## Signature Tripay

Semua signature dibuat otomatis oleh library. Rumusnya untuk referensi:

| Kebutuhan | Rumus (HMAC-SHA256, hex) | Method |
|---|---|---|
| Membuat transaksi | `merchantCode + merchantRef + amount` | `generateSignature($merchantRef, $amount)` |
| Open Payment | `merchantCode + method + merchantRef` | `generateOpenPaymentSignature($method, $merchantRef)` |
| Verifikasi callback | raw body JSON | `validateCallback($signature, $json)` |

```php
// contoh manual
$signature = hash_hmac('sha256', 'T0001' . 'INV-0001' . 50000, $privateKey);
```

Test vector bersama untuk semua bahasa ada di [`spec/test-vectors.json`](spec/test-vectors.json).

## Tanpa Laravel (PHP Native)

```php
require 'vendor/autoload.php';

use Ilhamuket\Tripay\Tripay;

$tripay = new Tripay(
    apiKey: getenv('TRIPAY_API_KEY'),
    privateKey: getenv('TRIPAY_PRIVATE_KEY'),
    merchantCode: getenv('TRIPAY_MERCHANT_CODE'),
    mode: Tripay::MODE_SANDBOX,                  // atau Tripay::MODE_PRODUCTION
    caBundle: null                               // opsional: path cacert.pem
);

$channels = $tripay->getPaymentChannels();

// Callback di PHP native
$json = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
if ($tripay->validateCallback($signature, $json)) {
    $callback = $tripay->parseCallback($json);
}
```

> Saat ini paket masih membutuhkan `illuminate/support` (dipasang otomatis oleh Composer) walau dipakai tanpa Laravel.

## Node.js / NestJS

SDK yang sama untuk JavaScript/TypeScript tersedia di npm: **[`@uket/tripay`](https://www.npmjs.com/package/@uket/tripay)**.
Paket ini tanpa dependency (memakai `fetch` bawaan Node 18+), dengan tipe TypeScript lengkap dan modul NestJS opsional.

```bash
npm install @uket/tripay
```

```ts
import { Tripay } from '@uket/tripay';

const tripay = new Tripay({ apiKey, privateKey, merchantCode, mode: 'sandbox' });
const trx = await tripay.createTransaction({
  method: 'QRIS', merchantRef: 'INV-001', amount: 50000,
  customerName: 'Budi', customerEmail: 'budi@example.com',
  orderItems: [{ name: 'Kaos', price: 50000, quantity: 1 }],
});
```

Dokumentasi lengkap: [`node/README.md`](node/README.md). Semua SDK mengikuti spesifikasi bersama
[`spec/tripay.openapi.yaml`](spec/tripay.openapi.yaml) (OpenAPI 3) dan lolos test vector yang sama.

## Referensi API

### Method `Tripay`

| Method | Endpoint Tripay | Return |
|---|---|---|
| `getPaymentChannels(?string $code)` | `GET merchant/payment-channel` | `PaymentChannelResponse` |
| `calculateFee(int $amount, ?string $code)` | `GET merchant/fee-calculator` | `FeeCalculatorResponse` |
| `getTransactions(array $params)` | `GET merchant/transactions` | `TransactionResponse` |
| `createTransaction(TransactionData $data)` | `POST transaction/create` | `CreateTransactionResponse` |
| `getTransactionDetail(string $reference)` | `GET transaction/detail` | `TransactionDetailResponse` |
| `checkTransactionStatus(string $reference)` | `GET transaction/check-status` | `CheckStatusResponse` |
| `getPaymentInstruction(string $code, ?string $payCode, ?int $amount, bool $allowHtml)` | `GET payment/instruction` | `InstructionResponse` |
| `createOpenPayment(string $method, ?string $merchantRef, ?string $customerName)` | `POST open-payment/create` | `OpenPaymentResponse` |
| `getOpenPaymentDetail(string $uuid)` | `GET open-payment/{uuid}/detail` | `OpenPaymentResponse` |
| `getOpenPaymentTransactions(string $uuid, array $params)` | `GET open-payment/{uuid}/transactions` | `OpenPaymentTransactionsResponse` |
| `validateCallback(string $signature, string $json)` | n/a | `bool` |
| `parseCallback(string $json)` | n/a | `CallbackData` |
| `generateSignature(string $merchantRef, int $amount)` | n/a | `string` |
| `generateOpenPaymentSignature(string $method, string $merchantRef = '')` | n/a | `string` |
| `sandbox()` / `production()` | n/a | `self` (mengganti mode instance ini) |
| `isSandbox()` / `isProduction()` / `getMode()` / `getMerchantCode()` | n/a | |

Semua response turunan `BaseResponse` punya `isSuccess()`, `getMessage()`, `getData()`, `toArray()`, `toJson()`.

### Exception

| Exception | Kapan |
|---|---|
| `Ilhamuket\Tripay\Exceptions\TripayApiException` | Tripay membalas HTTP ≥ 400 (mis. *Invalid API Key*, *Transaction not found*), respons bukan JSON, atau Open Payment dipanggil di sandbox |
| `Ilhamuket\Tripay\Exceptions\TripayConnectionException` | Gagal terhubung: DNS, timeout, atau sertifikat TLS |
| `InvalidArgumentException` | Field wajib `TransactionData` kosong, atau UUID Open Payment kosong |

```php
use Ilhamuket\Tripay\Exceptions\TripayApiException;
use Ilhamuket\Tripay\Exceptions\TripayConnectionException;

try {
    $response = Tripay::createTransaction($transaction);
} catch (TripayApiException $e) {
    report($e);    // $e->getMessage(), $e->getCode() = HTTP status
} catch (TripayConnectionException $e) {
    // coba lagi nanti
}
```

## Troubleshooting

**`cURL error 60` / `cURL error 77: SSL certificate problem`**
Server tidak bisa memverifikasi sertifikat Tripay. Biasanya terjadi di XAMPP/Laragon Windows atau server lama. Unduh `cacert.pem` dari
[curl.se/docs/caextract.html](https://curl.se/docs/caextract.html), lalu isi `TRIPAY_CA_BUNDLE=/path/to/cacert.pem`. Library ini
sengaja **tidak** mematikan verifikasi SSL, karena itu membuka celah pencurian API key.

**`Invalid API Key`**
Pastikan `TRIPAY_MODE` cocok dengan kredensial: API key sandbox (awalan `DEV-`) hanya berlaku di mode `sandbox`. Setelah mengubah `.env`, jalankan `php artisan config:clear`.

**`Payment channel is not enabled (BRIVA - T0001)`**
Channel belum aktif di merchant kamu. Aktifkan di dashboard Tripay → Merchant → Channel Pembayaran, atau pilih channel lain dari `getPaymentChannels()`.

**Callback selalu `Invalid signature`**
Gunakan raw body (`$request->getContent()` / `php://input`), bukan JSON yang di-encode ulang. Cek juga private key: sandbox dan production berbeda.

**`Transaction not found`**
Gunakan `reference` dari Tripay (mis. `DEV-T0001...`), bukan `merchant_ref` milikmu. Transaksi sandbox tidak bisa dicek di production, dan sebaliknya.

**Open Payment `hanya tersedia di mode production`**
Sandbox Tripay memang tidak menyediakan endpoint Open Payment. Uji Open Payment dengan akun production.

## FAQ

### Bagaimana cara integrasi Tripay di Laravel?
Pasang `composer require ilhamuket/tripay`, isi `TRIPAY_*` di `.env`, lalu panggil `Tripay::createTransaction()` dan buat route callback.
Lihat [Quick Start](#quick-start-membuat-transaksi-tripay) dan [Callback](#callback-tripay-webhook).

### Apakah library ini resmi dari Tripay?
Bukan. Ini library open source komunitas berlisensi MIT yang mengikuti [dokumentasi API resmi Tripay](https://tripay.co.id/developer).

### Bagaimana cara membuat signature Tripay di PHP?
`hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey)`. Library ini membuatnya otomatis saat `createTransaction()`. Lihat [Signature Tripay](#signature-tripay).

### Bagaimana cara validasi callback Tripay?
Hitung `hash_hmac('sha256', $rawBody, $privateKey)` dan bandingkan dengan header `X-Callback-Signature` memakai `hash_equals`. Di library ini cukup `Tripay::validateCallback($signature, $rawBody)`.

### Bagaimana cara testing Tripay di sandbox?
Set `TRIPAY_MODE=sandbox` dengan kredensial dari menu Simulator di dashboard Tripay. Pembayaran sandbox tidak memakai uang sungguhan, dan callback bisa diuji lewat Callback Tester di menu Simulator.

### Apa beda Closed Payment dan Open Payment Tripay?
Closed payment adalah tagihan sekali bayar dengan nominal tetap. Open payment adalah kode bayar/QRIS permanen yang bisa dibayar berulang dengan nominal bebas. Lihat [tabel perbandingan](#open-payment).

### Metode pembayaran apa saja yang didukung?
Semua channel Tripay: QRIS, Virtual Account (BRI, BNI, BCA, Mandiri, Permata, BSI, CIMB, dll.), OVO, DANA, ShopeePay, Alfamart, Indomaret, dan Alfamidi. Kode channel baru tetap bisa dipakai sebagai string biasa.

### Apakah bisa dipakai di CodeIgniter, Symfony, atau PHP native?
Bisa. Buat instance `new Tripay(...)` secara langsung, lihat [Tanpa Laravel](#tanpa-laravel-php-native).

### Ada versi Node.js / Express / NestJS?
Ada: [`@uket/tripay`](https://www.npmjs.com/package/@uket/tripay), lihat [Node.js / NestJS](#nodejs--nestjs).

---

## English Summary

**Tripay PHP & Laravel SDK.** An unofficial, community-maintained library for the [Tripay](https://tripay.co.id) Indonesian payment gateway.
It supports QRIS, virtual accounts, e-wallets (OVO, DANA, ShopeePay), retail outlets, open payment, fee calculation, transaction status,
and secure callback (webhook) verification, with signatures generated automatically. Laravel 10–12 and PHP 8.2+ are supported.
A Node.js/TypeScript/NestJS version is published as [`@uket/tripay`](https://www.npmjs.com/package/@uket/tripay).

```bash
composer require ilhamuket/tripay   # PHP / Laravel
npm install @uket/tripay            # Node.js / NestJS
```

## Changelog

Lihat [GitHub Releases](https://github.com/ilhamuket/tripay/releases). Ringkasan terbaru:

- **v1.1.0**: Open Payment (`createOpenPayment`, `getOpenPaymentDetail`, `getOpenPaymentTransactions`).
- **v1.0.12**: `isPaid()` mengenali pesan status `DIBAYAR` dari Tripay.
- **v1.0.11**: verifikasi TLS wajib (opsi `TRIPAY_CA_BUNDLE`); `isPaid()` tidak lagi membaca `UNPAID` sebagai lunas.

## Testing

```bash
composer test                       # PHPUnit
php scripts/verify-vectors.php      # test vector signature bersama
```

## Kontribusi

Issue dan pull request sangat diterima. Untuk perubahan endpoint atau rumus signature, perbarui juga
[`spec/tripay.openapi.yaml`](spec/tripay.openapi.yaml) dan [`spec/test-vectors.json`](spec/test-vectors.json) supaya SDK PHP dan Node tetap sejalan.
Jika menemukan celah keamanan, mohon laporkan secara privat lewat [GitHub Security Advisories](https://github.com/ilhamuket/tripay/security/advisories/new).

## Lisensi

MIT. Lihat [LICENSE.md](LICENSE.md).

## Tautan

- [Dokumentasi API resmi Tripay](https://tripay.co.id/developer)
- [Simulator / sandbox Tripay](https://tripay.co.id/simulator)
- [Paket Packagist `ilhamuket/tripay`](https://packagist.org/packages/ilhamuket/tripay)
- [Paket npm `@uket/tripay`](https://www.npmjs.com/package/@uket/tripay)

<!--
Kata kunci: tripay, library tripay, sdk tripay, tripay php, tripay laravel, tripay api, dokumentasi tripay, integrasi tripay,
payment gateway tripay, contoh kode tripay, callback tripay, signature tripay, tripay qris, tripay virtual account, tripay nodejs,
tripay nestjs, payment gateway indonesia laravel
-->
