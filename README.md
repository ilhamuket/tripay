# Tripay SDK: Library Payment Gateway Tripay untuk PHP, Laravel, Node.js, TypeScript & NestJS

[![Packagist](https://img.shields.io/packagist/v/ilhamuket/tripay.svg?style=flat-square&label=packagist)](https://packagist.org/packages/ilhamuket/tripay)
[![Packagist Downloads](https://img.shields.io/packagist/dt/ilhamuket/tripay.svg?style=flat-square&label=downloads)](https://packagist.org/packages/ilhamuket/tripay)
[![npm](https://img.shields.io/npm/v/@uket/tripay.svg?style=flat-square&label=npm)](https://www.npmjs.com/package/@uket/tripay)
[![npm Downloads](https://img.shields.io/npm/dm/@uket/tripay.svg?style=flat-square&label=npm%20downloads)](https://www.npmjs.com/package/@uket/tripay)
[![CI](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml/badge.svg)](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

**SDK / library Tripay multi-bahasa** untuk integrasi **[Tripay Payment Gateway](https://tripay.co.id)** dari
**PHP, Laravel, Node.js, TypeScript, Express, dan NestJS**. Fiturnya: buat transaksi QRIS, Virtual Account (BRIVA, BNIVA, BCAVA, Mandiri VA, dll.),
e-wallet (OVO, DANA, ShopeePay), gerai retail (Alfamart, Indomaret), dan Open Payment. Library ini juga memverifikasi callback,
mengecek status pembayaran, menghitung biaya, dan mengambil instruksi pembayaran. Signature Tripay dibuat otomatis.

Dokumentasi ini berisi **contoh kode integrasi Tripay yang lengkap dan siap pakai** untuk setiap bahasa: instalasi, konfigurasi
sandbox dan production, membuat transaksi, menangani callback, sampai troubleshooting error yang sering muncul.

| Bahasa / Framework | Paket | Instalasi |
|---|---|---|
| **PHP / Laravel** | [`ilhamuket/tripay`](https://packagist.org/packages/ilhamuket/tripay) (Packagist) | `composer require ilhamuket/tripay` |
| **Node.js / TypeScript / NestJS / Express** | [`@uket/tripay`](https://www.npmjs.com/package/@uket/tripay) (npm) | `npm install @uket/tripay` |
| Bahasa lain | Spesifikasi [OpenAPI](spec/tripay.openapi.yaml) + [test vector](spec/test-vectors.json) | Go, Python, dll. menyusul |

> ℹ️ Ini library **komunitas (tidak resmi)** dan tidak berafiliasi dengan PT Trijaya Digital Grup (Tripay).
> Dokumentasi API resmi: [tripay.co.id/developer](https://tripay.co.id/developer).

---

## Daftar Isi

- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi Sandbox & Production](#konfigurasi-sandbox--production)
- [Quick Start: Membuat Transaksi Tripay](#quick-start-membuat-transaksi-tripay)
- [Panduan PHP / Laravel](#panduan-php--laravel)
- [Panduan Node.js / TypeScript / NestJS](#panduan-nodejs--typescript--nestjs)
- [Callback Tripay (Webhook)](#callback-tripay-webhook): [Laravel](#callback-di-laravel) · [PHP native](#callback-di-php-native) · [Express](#callback-di-expressjs) · [NestJS](#callback-di-nestjs)
- [Open Payment](#open-payment)
- [Kode Metode Pembayaran](#kode-metode-pembayaran)
- [Signature Tripay](#signature-tripay)
- [Referensi API](#referensi-api)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [English Summary](#english-summary)
- [Changelog](#changelog) · [Kontribusi](#kontribusi) · [Lisensi](#lisensi)

---

## Fitur

| Fitur | PHP / Laravel | Node.js / NestJS |
|---|:---:|:---:|
| Closed Payment: QRIS, Virtual Account, e-wallet, retail | ✅ | ✅ |
| Open Payment: kode bayar / QRIS berulang (khusus production) | ✅ | ✅ |
| Verifikasi callback `X-Callback-Signature` (constant-time) | ✅ | ✅ |
| Signature Tripay otomatis | ✅ | ✅ |
| Daftar channel, kalkulator biaya, detail, cek status, daftar transaksi, instruksi | ✅ | ✅ |
| Validasi input sebelum request | ✅ (`TransactionData`) | ✅ (bilangan bulat, tanggal, wajib isi) |
| Integrasi framework | Laravel 10–12: service provider, facade, config | NestJS: `TripayModule.forRoot/forRootAsync` |
| Tipe data | Response object bertipe | TypeScript lengkap |
| Dependency | Guzzle 7 | **Tanpa dependency** (`fetch` bawaan) |
| Verifikasi TLS selalu aktif, status `UNPAID` tidak pernah terbaca lunas | ✅ | ✅ |
| Diuji di CI dan langsung ke sandbox Tripay | ✅ PHP 8.2 & 8.3 | ✅ Node 22 |

## Persyaratan

| | PHP / Laravel | Node.js / NestJS |
|---|---|---|
| Runtime | PHP 8.2 atau lebih baru | Node.js 18 atau lebih baru |
| Framework (opsional) | Laravel 10, 11, atau 12 | NestJS 9+ / Express / Fastify / framework lain |
| Ekstensi / dependency | `json`, `curl` (lewat Guzzle 7) | tidak ada |
| Akun Tripay | merchant sandbox (menu **Simulator**) atau production | sama |

## Instalasi

**PHP / Laravel**

```bash
composer require ilhamuket/tripay
```

Di Laravel, service provider dan facade terdaftar otomatis. Untuk mem-publish file config (opsional):

```bash
php artisan vendor:publish --tag=tripay-config
```

**Node.js / TypeScript / NestJS**

```bash
npm install @uket/tripay
# atau: pnpm add @uket/tripay / yarn add @uket/tripay
```

## Konfigurasi Sandbox & Production

Ambil kredensial **sandbox** dari dashboard Tripay → **Simulator** → Merchant. Kredensial **production** ada di menu **Merchant** → API Key.
Nama variabelnya sama untuk semua bahasa:

```env
TRIPAY_MODE=sandbox            # sandbox | production
TRIPAY_MERCHANT_CODE=T0001
TRIPAY_API_KEY=DEV-xxxxxxxxxxxxxxxx
TRIPAY_PRIVATE_KEY=xxxxx-xxxxx-xxxxx-xxxxx-xxxxx

# PHP saja (opsional): path CA bundle bila muncul "cURL error 60/77: SSL certificate problem"
# TRIPAY_CA_BUNDLE=/path/to/cacert.pem
```

| Mode | Base URL API |
|---|---|
| `sandbox` | `https://tripay.co.id/api-sandbox` |
| `production` | `https://tripay.co.id/api` |

- **Laravel** membaca `.env` lewat `config/tripay.php`.
- **Node.js** menerima config di konstruktor: `new Tripay({ apiKey, privateKey, merchantCode, mode })`. Opsi lain: `timeoutMs` (default 30 detik), `baseUrl`, dan `fetch`.

> 🔐 Jangan commit `.env` atau menaruh private key di kode frontend/browser. Private key dipakai untuk signature dan verifikasi callback,
> jadi SDK ini hanya untuk dijalankan di **server**.

## Quick Start: Membuat Transaksi Tripay

**PHP / Laravel**

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
    ->setCallbackUrl('https://tokokamu.com/api/callback/tripay')
    ->setExpiryHours(24);

$response = Tripay::createTransaction($transaction);

$response->getReference();   // referensi Tripay, mis. DEV-T0001000012345ABCDE
$response->getCheckoutUrl(); // halaman pembayaran Tripay (bisa langsung redirect)
$response->getQrUrl();       // gambar QR (QRIS)
$response->getPayCode();     // nomor VA / kode bayar
```

**Node.js / TypeScript**

```ts
import { Tripay } from '@uket/tripay';

const tripay = new Tripay({
  apiKey: process.env.TRIPAY_API_KEY!,
  privateKey: process.env.TRIPAY_PRIVATE_KEY!,
  merchantCode: process.env.TRIPAY_MERCHANT_CODE!,
  mode: process.env.TRIPAY_MODE === 'production' ? 'production' : 'sandbox',
});

const trx = await tripay.createTransaction({
  method: 'QRIS',
  merchantRef: `INV-${Date.now()}`,
  amount: 100000,
  customerName: 'Budi Santoso',
  customerEmail: 'budi@example.com',
  customerPhone: '081234567890',
  orderItems: [{ name: 'Kaos Polos', price: 50000, quantity: 2 }],
  callbackUrl: 'https://tokokamu.com/api/callback/tripay',
  expiresInMinutes: 24 * 60,
});

trx.reference;     // referensi Tripay
trx.checkout_url;  // halaman pembayaran Tripay
trx.qr_url;        // gambar QR (QRIS)
trx.pay_code;      // nomor VA / kode bayar
```

Setelah pelanggan membayar, Tripay mengirim [callback](#callback-tripay-webhook) ke `callback_url`. Di sanalah status order ditandai lunas.

---

## Panduan PHP / Laravel

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
use Ilhamuket\Tripay\Data\TransactionData;
use Ilhamuket\Tripay\PaymentMethod;

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

### Tanpa Laravel (PHP Native, CodeIgniter, Symfony)

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
```

> Paket PHP saat ini masih membutuhkan `illuminate/support` (dipasang otomatis oleh Composer) walau dipakai tanpa Laravel.

### Error Handling (PHP)

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

---

## Panduan Node.js / TypeScript / NestJS

`@uket/tripay` tidak punya dependency: paket ini memakai `fetch` bawaan Node 18+ dan dilengkapi tipe TypeScript.
Field respons mengikuti nama asli dari Tripay (`snake_case`), sedangkan input memakai `camelCase`.

```ts
import { Tripay } from '@uket/tripay';
// CommonJS: const { Tripay } = require('@uket/tripay');

const tripay = new Tripay({
  apiKey: process.env.TRIPAY_API_KEY!,
  privateKey: process.env.TRIPAY_PRIVATE_KEY!,
  merchantCode: process.env.TRIPAY_MERCHANT_CODE!,
  mode: 'sandbox',          // atau 'production'
  timeoutMs: 30_000,        // opsional
});
```

### Channel, Fee, dan Transaksi

```ts
// Channel pembayaran & biaya
const channels = await tripay.getPaymentChannels();          // PaymentChannel[]
const qris = await tripay.getPaymentChannels('QRIS');
const fees = await tripay.calculateFee(100000, 'BRIVA');      // [{ code, fee, total_fee: { merchant, customer } }]

// Buat transaksi closed payment
const trx = await tripay.createTransaction({
  method: 'BRIVA',
  merchantRef: 'INV-2026-0001',
  amount: 150000,
  customerName: 'Budi Santoso',
  customerEmail: 'budi@example.com',
  customerPhone: '081234567890',
  orderItems: [{ sku: 'SKU-001', name: 'Sepatu Lari', price: 150000, quantity: 1 }],
  callbackUrl: 'https://tokokamu.com/api/callback/tripay',
  returnUrl: 'https://tokokamu.com/orders/INV-2026-0001',
  expiresInMinutes: 120,     // atau expiredTime: unix timestamp (detik)
});
console.log(trx.reference, trx.pay_code, trx.checkout_url);

// Detail, status, daftar transaksi
const detail = await tripay.getTransactionDetail(trx.reference);   // detail.status: 'UNPAID' | 'PAID' | ...
const status = await tripay.checkTransactionStatus(trx.reference); // { message, status } ("DIBAYAR" dibaca 'PAID')
const { data, pagination } = await tripay.getTransactions({ page: 1, perPage: 50, status: 'PAID' });

// Instruksi pembayaran
const steps = await tripay.getPaymentInstruction({ code: 'BRIVA', payCode: trx.pay_code ?? undefined });
```

### NestJS Module

```ts
// app.module.ts
import { ConfigModule, ConfigService } from '@nestjs/config';
import { TripayModule } from '@uket/tripay/nestjs';

@Module({
  imports: [
    ConfigModule.forRoot(),
    TripayModule.forRootAsync({
      inject: [ConfigService],
      useFactory: (c: ConfigService) => ({
        apiKey: c.getOrThrow('TRIPAY_API_KEY'),
        privateKey: c.getOrThrow('TRIPAY_PRIVATE_KEY'),
        merchantCode: c.getOrThrow('TRIPAY_MERCHANT_CODE'),
        mode: c.get('TRIPAY_MODE') === 'production' ? 'production' : 'sandbox',
      }),
    }),
  ],
})
export class AppModule {}

// payment.service.ts
import { Tripay } from '@uket/tripay';

@Injectable()
export class PaymentService {
  constructor(private readonly tripay: Tripay) {}

  channels() {
    return this.tripay.getPaymentChannels();
  }
}
```

### Error Handling (Node.js)

| Class | Kapan |
|---|---|
| `TripayValidationError` | Input tidak valid (sebelum request), signature callback salah, atau Open Payment dipanggil di sandbox |
| `TripayApiError` | Tripay membalas HTTP ≥ 400, `success: false`, atau body bukan JSON. Ada `.status` dan `.body` |
| `TripayConnectionError` | Jaringan, DNS, TLS, atau timeout |

```ts
import { TripayApiError, TripayConnectionError, TripayValidationError } from '@uket/tripay';

try {
  await tripay.createTransaction(input);
} catch (err) {
  if (err instanceof TripayApiError) console.error(err.status, err.message);
  else if (err instanceof TripayConnectionError) { /* coba lagi nanti */ }
  else if (err instanceof TripayValidationError) { /* perbaiki input */ }
  else throw err;
}
```

Dokumentasi Node lebih detail: [`node/README.md`](node/README.md).

---

## Callback Tripay (Webhook)

Tripay mengirim `POST` JSON ke `callback_url` setiap kali status pembayaran berubah (PAID, EXPIRED, FAILED, REFUND).
Ada dua header penting: `X-Callback-Signature` (HMAC-SHA256 dari raw body) dan `X-Callback-Event: payment_status`.

**Checklist callback (semua bahasa):**

- Verifikasi signature dari **raw body** (byte asli request), bukan hasil `json_encode` / `JSON.stringify` ulang.
- Cek header `X-Callback-Event` bernilai `payment_status`.
- Proses secara **idempotent** (callback bisa dikirim ulang) dan cocokkan nominal dengan order.
- Balas `{"success": true}`. Jika tidak, Tripay mengirim ulang (jeda 2 menit, maksimal 3 kali).
- Payload Open Payment ditandai `is_closed_payment = 0`.
- Uji dengan **Callback Tester** di dashboard Tripay (sandbox: menu Simulator → Callback).
- Bila server memakai whitelist IP, lihat daftar IP callback di [dokumentasi Tripay](https://tripay.co.id/developer).

### Callback di Laravel

```php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Ilhamuket\Tripay\Facades\Tripay;

class TripayCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $json = $request->getContent();                          // RAW body
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

        if ($order->status === 'paid') {                         // idempotent
            return response()->json(['success' => true]);
        }

        if ($callback->isPaid()) {
            $order->update(['status' => 'paid', 'paid_at' => $callback->getPaidAtDateTime()]);
        } elseif ($callback->isExpired() || $callback->isFailed()) {
            $order->update(['status' => strtolower($callback->getStatus())]);
        }

        return response()->json(['success' => true]);
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

### Callback di PHP Native

```php
$json = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

if (! $tripay->validateCallback($signature, $json)) {
    http_response_code(403);
    exit(json_encode(['success' => false]));
}

$callback = $tripay->parseCallback($json);
// ... update order
echo json_encode(['success' => true]);
```

### Callback di Express.js

```ts
import express from 'express';

const app = express();

app.post('/api/callback/tripay', express.raw({ type: 'application/json' }), async (req, res) => {
  if (req.header('X-Callback-Event') !== 'payment_status') {
    return res.status(400).json({ success: false, message: 'Unrecognized event' });
  }
  try {
    // req.body adalah Buffer (raw), signature diverifikasi di dalam parseCallback
    const payload = tripay.parseCallback(req.body, req.header('X-Callback-Signature'));
    // payload.status: 'PAID' | 'EXPIRED' | 'FAILED' | 'REFUND'
    // ... update order secara idempotent
    res.json({ success: true });
  } catch {
    res.status(403).json({ success: false, message: 'Invalid signature' });
  }
});
```

### Callback di NestJS

```ts
// main.ts
const app = await NestFactory.create(AppModule, { rawBody: true });

// tripay-callback.controller.ts
import { Controller, Headers, HttpCode, Post, RawBodyRequest, Req, ForbiddenException } from '@nestjs/common';
import { Request } from 'express';
import { Tripay, TripayValidationError } from '@uket/tripay';

@Controller('api/callback')
export class TripayCallbackController {
  constructor(private readonly tripay: Tripay) {}

  @Post('tripay')
  @HttpCode(200)
  handle(@Req() req: RawBodyRequest<Request>, @Headers('x-callback-signature') signature: string) {
    try {
      const payload = this.tripay.parseCallback(req.rawBody!, signature);
      // ... update order secara idempotent
      return { success: true };
    } catch (err) {
      if (err instanceof TripayValidationError) throw new ForbiddenException('Invalid signature');
      throw err;
    }
  }
}
```

---

## Open Payment

Open Payment adalah kode bayar (VA) atau QRIS **yang bisa dibayar berulang kali** dengan nominal bebas, misalnya untuk top-up saldo
member atau donasi. Endpoint ini **hanya tersedia di production**. Di mode sandbox, kedua SDK langsung menolak dengan pesan yang jelas.

**PHP / Laravel**

```php
// TRIPAY_MODE=production
$op = Tripay::createOpenPayment(method: 'BCAVA', merchantRef: 'MEMBER-0042', customerName: 'Budi');

$uuid = $op->getUuid();          // simpan untuk cek detail / riwayat
$op->getPayCode();               // nomor VA (null untuk QRIS)
$op->getQrString();              // isi QRIS (null untuk VA)

$detail  = Tripay::getOpenPaymentDetail($uuid);
$history = Tripay::getOpenPaymentTransactions($uuid, [
    'start_date' => '2026-11-01 00:00:00',   // format Y-m-d H:i:s
    'end_date'   => '2026-11-30 23:59:59',
    'per_page'   => 50,                      // maks 100
]);
```

**Node.js**

```ts
const tripay = new Tripay({ ...config, mode: 'production' });

const op = await tripay.createOpenPayment({ method: 'QRIS', merchantRef: 'MEMBER-0042', customerName: 'Budi' });
await tripay.getOpenPaymentDetail(op.uuid);
const { data, pagination } = await tripay.getOpenPaymentTransactions(op.uuid, {
  startDate: '2026-11-01 00:00:00',
  endDate: '2026-11-30 23:59:59',
  perPage: 50,
});
```

| | Closed Payment | Open Payment |
|---|---|---|
| Nominal | Tetap, ditentukan merchant | Bebas, ditentukan pembayar |
| Berapa kali dibayar | Sekali | Berulang kali |
| Kedaluwarsa | Ya (`expired_time`) | Tidak |
| Sandbox | ✅ | ❌ (hanya production) |
| Signature | `merchantCode + merchantRef + amount` | `merchantCode + method + merchantRef` |

## Kode Metode Pembayaran

Channel yang aktif bisa berbeda per merchant; aktifkan di dashboard Tripay → Merchant → Channel Pembayaran.
Di PHP tersedia konstanta `Ilhamuket\Tripay\PaymentMethod`, di Node tersedia tipe `KnownChannelCode`.

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

Kode channel baru dari Tripay tetap bisa dipakai sebagai string biasa di kedua SDK.

## Signature Tripay

Semua signature dibuat otomatis oleh SDK. Rumusnya, untuk referensi:

| Kebutuhan | Rumus (HMAC-SHA256, hex) | PHP | Node.js |
|---|---|---|---|
| Membuat transaksi | `merchantCode + merchantRef + amount` | `generateSignature($ref, $amount)` | `generateSignature(ref, amount)` |
| Open Payment | `merchantCode + method + merchantRef` | `generateOpenPaymentSignature($method, $ref)` | `generateOpenPaymentSignature(method, ref)` |
| Verifikasi callback | raw body JSON | `validateCallback($signature, $json)` | `verifyCallback(rawBody, signature)` |

```php
// PHP manual
$signature = hash_hmac('sha256', 'T0001' . 'INV-0001' . 50000, $privateKey);
```

```ts
// Node.js manual
import { createHmac } from 'node:crypto';
const signature = createHmac('sha256', privateKey).update('T0001' + 'INV-0001' + 50000).digest('hex');
```

Kalau kamu menulis SDK di bahasa lain, cocokkan hasilnya dengan [`spec/test-vectors.json`](spec/test-vectors.json).

## Referensi API

| Endpoint Tripay | PHP (`Tripay`) | Node.js (`Tripay`) |
|---|---|---|
| `GET merchant/payment-channel` | `getPaymentChannels(?string $code)` | `getPaymentChannels(code?)` |
| `GET merchant/fee-calculator` | `calculateFee(int $amount, ?string $code)` | `calculateFee(amount, code?)` |
| `GET merchant/transactions` | `getTransactions(array $params)` | `getTransactions({ page, perPage, ... })` |
| `POST transaction/create` | `createTransaction(TransactionData $data)` | `createTransaction(input)` |
| `GET transaction/detail` | `getTransactionDetail(string $reference)` | `getTransactionDetail(reference)` |
| `GET transaction/check-status` | `checkTransactionStatus(string $reference)` | `checkTransactionStatus(reference)` |
| `GET payment/instruction` | `getPaymentInstruction($code, $payCode, $amount, $allowHtml)` | `getPaymentInstruction({ code, payCode, amount, allowHtml })` |
| `POST open-payment/create` | `createOpenPayment($method, $merchantRef, $customerName)` | `createOpenPayment({ method, merchantRef, customerName })` |
| `GET open-payment/{uuid}/detail` | `getOpenPaymentDetail(string $uuid)` | `getOpenPaymentDetail(uuid)` |
| `GET open-payment/{uuid}/transactions` | `getOpenPaymentTransactions(string $uuid, array $params)` | `getOpenPaymentTransactions(uuid, params)` |
| Callback | `validateCallback()` + `parseCallback()` | `verifyCallback()` / `parseCallback()` |

Spesifikasi lengkap endpoint dan model data (OpenAPI 3): [`spec/tripay.openapi.yaml`](spec/tripay.openapi.yaml).

## Troubleshooting

**`cURL error 60` / `cURL error 77: SSL certificate problem` (PHP)**
Server tidak bisa memverifikasi sertifikat Tripay. Biasanya terjadi di XAMPP/Laragon Windows atau server lama. Unduh `cacert.pem` dari
[curl.se/docs/caextract.html](https://curl.se/docs/caextract.html), lalu isi `TRIPAY_CA_BUNDLE=/path/to/cacert.pem`.
Untuk Node.js, pakai `NODE_EXTRA_CA_CERTS=/path/to/ca.pem`. Kedua SDK sengaja **tidak** mematikan verifikasi SSL, karena itu membuka celah pencurian API key.

**`Invalid API Key`**
Pastikan mode cocok dengan kredensial: API key sandbox (awalan `DEV-`) hanya berlaku di mode `sandbox`. Di Laravel, jalankan `php artisan config:clear` setelah mengubah `.env`.

**`Payment channel is not enabled (BRIVA - T0001)`**
Channel belum aktif di merchant kamu. Aktifkan di dashboard Tripay → Merchant → Channel Pembayaran, atau pilih channel dari `getPaymentChannels()`.

**Callback selalu `Invalid signature`**
Gunakan raw body:
- Laravel: `$request->getContent()`
- PHP native: `php://input`
- Express: `express.raw()`
- NestJS: `rawBody: true`

Jangan pakai JSON yang di-encode ulang. Cek juga private key, karena sandbox dan production berbeda.

**`Transaction not found`**
Gunakan `reference` dari Tripay (mis. `DEV-T0001...`), bukan `merchant_ref` milikmu. Transaksi sandbox tidak bisa dicek di production, dan sebaliknya.

**Open Payment `hanya tersedia di mode production`**
Sandbox Tripay memang tidak menyediakan endpoint Open Payment. Uji Open Payment dengan akun production.

## FAQ

### Bagaimana cara integrasi Tripay di Laravel?
Jalankan `composer require ilhamuket/tripay`, isi `TRIPAY_*` di `.env`, panggil `Tripay::createTransaction()`, lalu buat route callback.
Lihat [Quick Start](#quick-start-membuat-transaksi-tripay) dan [Callback di Laravel](#callback-di-laravel).

### Bagaimana cara integrasi Tripay di Node.js, Express, atau NestJS?
Jalankan `npm install @uket/tripay`, buat `new Tripay({ ... })` (atau pakai `TripayModule` di NestJS), panggil `createTransaction()`,
lalu verifikasi callback dengan `parseCallback(rawBody, signature)`. Lihat [Panduan Node.js](#panduan-nodejs--typescript--nestjs).

### Apakah library ini resmi dari Tripay?
Bukan. Ini library open source komunitas berlisensi MIT yang mengikuti [dokumentasi API resmi Tripay](https://tripay.co.id/developer).

### Bagaimana cara membuat signature Tripay?
HMAC-SHA256 dari `merchantCode + merchantRef + amount` dengan private key, dalam format hex. Kedua SDK membuatnya otomatis. Lihat [Signature Tripay](#signature-tripay).

### Bagaimana cara validasi callback Tripay?
Hitung HMAC-SHA256 dari raw body dengan private key, lalu bandingkan dengan header `X-Callback-Signature` secara constant-time.
Di PHP pakai `Tripay::validateCallback()`, di Node.js pakai `tripay.parseCallback()` atau `tripay.verifyCallback()`.

### Bagaimana cara testing Tripay di sandbox?
Set mode `sandbox` dengan kredensial dari menu Simulator di dashboard Tripay. Pembayaran sandbox tidak memakai uang sungguhan, dan callback bisa diuji lewat Callback Tester di menu Simulator.

### Apa beda Closed Payment dan Open Payment Tripay?
Closed payment adalah tagihan sekali bayar dengan nominal tetap. Open payment adalah kode bayar/QRIS permanen yang bisa dibayar berulang dengan nominal bebas. Lihat [tabel perbandingan](#open-payment).

### Metode pembayaran apa saja yang didukung?
Semua channel Tripay: QRIS, Virtual Account (BRI, BNI, BCA, Mandiri, Permata, BSI, CIMB, dll.), OVO, DANA, ShopeePay, Alfamart, Indomaret, dan Alfamidi.

### Apakah bisa dipakai di CodeIgniter, Symfony, Fastify, atau Next.js?
Bisa. PHP bisa dipakai di framework apa pun lewat `new Tripay(...)`. Versi Node bisa dipakai di Fastify, Koa, atau Next.js (API route / server action),
asal **hanya di server**, karena private key tidak boleh terkirim ke browser.

### Bagaimana dengan Go, Python, atau bahasa lain?
Belum tersedia. Spesifikasi [OpenAPI](spec/tripay.openapi.yaml) dan [test vector](spec/test-vectors.json) di repo ini bisa dipakai sebagai dasar.
Kontribusi sangat diterima.

---

## English Summary

**Tripay SDK for PHP, Laravel, Node.js, TypeScript & NestJS.** An unofficial, community-maintained library for the
[Tripay](https://tripay.co.id) Indonesian payment gateway. It supports QRIS, virtual accounts, e-wallets (OVO, DANA, ShopeePay),
retail outlets, open payment, fee calculation, transaction status, and secure callback (webhook) verification, with signatures generated automatically.

```bash
composer require ilhamuket/tripay   # PHP 8.2+ / Laravel 10–12
npm install @uket/tripay            # Node.js 18+ / TypeScript / NestJS
```

## Changelog

Lihat [GitHub Releases](https://github.com/ilhamuket/tripay/releases). Ringkasan terbaru:

| PHP | Node.js | Perubahan |
|---|---|---|
| v1.1.1 | 0.2.1 | Dokumentasi lengkap dan metadata paket |
| v1.1.0 | 0.2.0 | Open Payment |
| v1.0.12 | 0.1.1* | Status lunas `DIBAYAR` dikenali |
| v1.0.11 | 0.1.0 | Verifikasi TLS wajib; `UNPAID` tidak lagi terbaca lunas; rilis pertama Node.js |

<sub>*0.1.1 tidak dipublish terpisah; perubahannya masuk di 0.2.0.</sub>

## Testing

```bash
composer test                       # PHP: PHPUnit
php scripts/verify-vectors.php      # PHP: test vector signature bersama
cd node && npm install && npm test  # Node.js
```

## Kontribusi

Issue dan pull request sangat diterima, termasuk SDK untuk bahasa baru. Untuk perubahan endpoint atau rumus signature, perbarui juga
[`spec/tripay.openapi.yaml`](spec/tripay.openapi.yaml) dan [`spec/test-vectors.json`](spec/test-vectors.json) supaya semua SDK tetap sejalan.
Jika menemukan celah keamanan, mohon laporkan secara privat lewat [GitHub Security Advisories](https://github.com/ilhamuket/tripay/security/advisories/new).

## Lisensi

MIT. Lihat [LICENSE.md](LICENSE.md).

## Tautan

- [Dokumentasi API resmi Tripay](https://tripay.co.id/developer)
- [Simulator / sandbox Tripay](https://tripay.co.id/simulator)
- [Paket Packagist `ilhamuket/tripay`](https://packagist.org/packages/ilhamuket/tripay)
- [Paket npm `@uket/tripay`](https://www.npmjs.com/package/@uket/tripay)

<!--
Kata kunci: tripay, library tripay, sdk tripay, tripay php, tripay laravel, tripay nodejs, tripay node js, tripay typescript,
tripay nestjs, tripay express, tripay api, dokumentasi tripay, integrasi tripay, payment gateway tripay, contoh kode tripay,
callback tripay, signature tripay, tripay qris, tripay virtual account, payment gateway indonesia
-->
