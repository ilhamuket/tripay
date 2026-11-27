# Spec bersama Tripay SDK

Acuan untuk semua versi SDK (PHP di root repo ini, Node di `node/`, bahasa lain menyusul).

- `tripay.openapi.yaml`: endpoint dan model. Client per bahasa boleh di-generate dari sini.
- `test-vectors.json`: input dan hasil yang diharapkan untuk signature transaksi dan verifikasi callback. Semua SDK wajib lolos.

## Aturan untuk semua bahasa

1. **Ditulis tangan per bahasa, wajib lolos `test-vectors.json`:**
   - signature transaksi = `HMAC-SHA256(merchant_code + merchant_ref + amount, private_key)`, hex;
   - verifikasi callback = `HMAC-SHA256(raw body, private_key)` dibandingkan constant-time dengan `X-Callback-Signature`.
2. **`transaction/create` dikirim sebagai form** gaya PHP (`order_items[0][name]=...`).
3. **Status check-status dicocokkan per kata**, supaya `UNPAID` tidak terbaca `PAID`.
4. **TLS selalu diverifikasi.** Tidak ada fallback `verify=false`.
5. **Angka** (`amount`, `price`, `quantity`) divalidasi sebagai bilangan bulat aman sebelum request.

## Perintah

```bash
node scripts/gen-test-vectors.mjs   # buat ulang vector
php scripts/verify-vectors.php      # cek rumus PHP
cd node && npm install && npm test  # test SDK Node
```
