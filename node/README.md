# @uket/tripay: Tripay SDK untuk Node.js, TypeScript & NestJS

[![npm](https://img.shields.io/npm/v/@uket/tripay.svg?style=flat-square)](https://www.npmjs.com/package/@uket/tripay)
[![CI](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml/badge.svg)](https://github.com/ilhamuket/tripay/actions/workflows/ci.yml)

**Library Tripay untuk Node.js / TypeScript / NestJS / Express**, untuk integrasi [Tripay Payment Gateway](https://tripay.co.id):
QRIS, Virtual Account, e-wallet, retail, Open Payment, dan verifikasi callback. Signature dibuat otomatis.
Tanpa dependency (memakai `fetch` bawaan Node 18+), dengan tipe TypeScript lengkap dan modul NestJS opsional.

> ℹ️ Library komunitas (tidak resmi), tidak berafiliasi dengan Tripay. Versi PHP/Laravel: [`ilhamuket/tripay`](https://github.com/ilhamuket/tripay).

```bash
npm install @uket/tripay
```

## Pakai langsung

```ts
import { Tripay } from '@uket/tripay';

const tripay = new Tripay({
  apiKey: process.env.TRIPAY_API_KEY!,
  privateKey: process.env.TRIPAY_PRIVATE_KEY!,
  merchantCode: process.env.TRIPAY_MERCHANT_CODE!,
  mode: 'sandbox', // atau 'production'
});

const channels = await tripay.getPaymentChannels();
const fees = await tripay.calculateFee(50000, 'BRIVA');

const tx = await tripay.createTransaction({
  method: 'BRIVA',
  merchantRef: 'INV-0001',
  amount: 50000,
  customerName: 'Budi',
  customerEmail: 'budi@example.com',
  orderItems: [{ sku: 'SKU-1', name: 'Kaos', price: 25000, quantity: 2 }],
  callbackUrl: 'https://example.com/tripay/callback',
  returnUrl: 'https://example.com/orders/INV-0001',
  expiresInMinutes: 60,
});
console.log(tx.reference, tx.pay_code, tx.checkout_url);

await tripay.getTransactionDetail(tx.reference);
await tripay.checkTransactionStatus(tx.reference); // { message, status: 'UNPAID' | 'PAID' | ... }
await tripay.getTransactions({ page: 1, perPage: 50, status: 'PAID' });
await tripay.getPaymentInstruction({ code: 'BRIVA', payCode: tx.pay_code ?? undefined });
```

`signature` transaksi dihitung otomatis. Input divalidasi sebelum request: `amount`, `price`, dan `quantity` harus bilangan bulat yang aman.

## Open Payment (khusus production)

Kode bayar / QRIS yang bisa dibayar berulang kali. Di mode sandbox SDK langsung melempar `TripayValidationError`, karena sandbox Tripay tidak menyediakan endpoint ini.

```ts
const tripay = new Tripay({ ...config, mode: 'production' });

const op = await tripay.createOpenPayment({ method: 'QRIS', merchantRef: 'MEMBER-0042', customerName: 'Budi' });
console.log(op.uuid, op.qr_string ?? op.pay_code);

await tripay.getOpenPaymentDetail(op.uuid);
const { data, pagination } = await tripay.getOpenPaymentTransactions(op.uuid, {
  startDate: '2026-11-01 00:00:00', // "Y-m-d H:i:s"
  endDate: '2026-11-30 23:59:59',
  perPage: 50, // maks 100
});
```

Pembayaran Open Payment dikirim ke callback dengan `is_closed_payment: 0`.

## Callback

Verifikasi selalu memakai **raw body** asli. Hasil `JSON.stringify(req.body)` bisa berbeda byte-nya, sehingga signature tidak cocok.

```ts
// Express
app.post('/tripay/callback', express.raw({ type: 'application/json' }), (req, res) => {
  const payload = tripay.parseCallback(req.body, req.header('X-Callback-Signature'));
  // payload.status: 'PAID' | 'EXPIRED' | 'FAILED' | 'REFUND'
  // Proses secara idempotent (callback bisa dikirim ulang) dan cocokkan amount dengan order.
  res.json({ success: true });
});
```

`parseCallback` melempar `TripayValidationError` bila signature salah atau body bukan JSON. Untuk sekadar cek, pakai `tripay.verifyCallback(rawBody, signature)`.

## NestJS

```ts
// app.module.ts
import { TripayModule } from '@uket/tripay/nestjs';

@Module({
  imports: [
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

// main.ts: aktifkan raw body untuk callback
const app = await NestFactory.create(AppModule, { rawBody: true });

// payment.controller.ts
@Controller('tripay')
export class TripayCallbackController {
  constructor(private readonly tripay: Tripay) {}

  @Post('callback')
  @HttpCode(200)
  callback(@Req() req: RawBodyRequest<Request>, @Headers('x-callback-signature') sig: string) {
    const payload = this.tripay.parseCallback(req.rawBody!, sig);
    // ...
    return { success: true };
  }
}
```

## Error

| Kelas | Kapan |
|---|---|
| `TripayValidationError` | Input tidak valid (sebelum request) atau signature callback salah |
| `TripayApiError` | Tripay membalas HTTP >= 400, `success: false`, atau body bukan JSON. Ada `.status` dan `.body` |
| `TripayConnectionError` | Jaringan, DNS, TLS, atau timeout (`timeoutMs`, default 30 detik) |

Verifikasi TLS tidak pernah dimatikan. Kalau ada error sertifikat, perbaiki CA bundle di server, jangan dilewati.

## Lisensi

MIT
