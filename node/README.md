# @uket/tripay

SDK Tripay Payment Gateway untuk Node.js dan TypeScript. Tanpa dependency (pakai `fetch` bawaan Node 18+), dengan modul NestJS opsional.

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
