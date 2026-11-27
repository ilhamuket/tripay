// Membuat spec/test-vectors.json. Semua SDK (PHP, Node, Go, ...) wajib lolos vector yang sama.
// Jalankan: node scripts/gen-test-vectors.mjs
import { createHmac } from 'node:crypto';
import { writeFileSync } from 'node:fs';

const hmac = (data, key) => createHmac('sha256', key).update(data).digest('hex');

const signature = [
  { merchantCode: 'T0001', privateKey: 'private-key-sandbox', merchantRef: 'INV-0001', amount: 50000 },
  { merchantCode: 'T0001', privateKey: 'private-key-sandbox', merchantRef: 'INV-0002', amount: 1 },
  { merchantCode: 'T12345', privateKey: 'aBcD3-fGh1J-kLmN0', merchantRef: 'ORDER/2026/11/27-001', amount: 1250000 },
].map((v) => ({ ...v, expected: hmac(v.merchantCode + v.merchantRef + v.amount, v.privateKey) }));

const paidBody = JSON.stringify({
  reference: 'DEV-T0001000012345ABCDE',
  merchant_ref: 'INV-0001',
  payment_method: 'BRI Virtual Account',
  payment_method_code: 'BRIVA',
  total_amount: 54250,
  fee_merchant: 0,
  fee_customer: 4250,
  total_fee: 4250,
  amount_received: 50000,
  is_closed_payment: 1,
  status: 'PAID',
  paid_at: 1795740000,
  note: null,
});

const callbackKey = 'private-key-sandbox';
const callback = [
  { name: 'valid', privateKey: callbackKey, rawBody: paidBody, signature: hmac(paidBody, callbackKey), valid: true },
  {
    name: 'body diubah (status EXPIRED) dengan signature lama',
    privateKey: callbackKey,
    rawBody: paidBody.replace('"PAID"', '"EXPIRED"'),
    signature: hmac(paidBody, callbackKey),
    valid: false,
  },
  {
    name: 'private key lain',
    privateKey: 'private-key-lain',
    rawBody: paidBody,
    signature: hmac(paidBody, callbackKey),
    valid: false,
  },
  { name: 'signature kosong', privateKey: callbackKey, rawBody: paidBody, signature: '', valid: false },
  { name: 'signature panjang beda', privateKey: callbackKey, rawBody: paidBody, signature: 'abc123', valid: false },
];

writeFileSync(
  new URL('../spec/test-vectors.json', import.meta.url),
  JSON.stringify({ signature, callback }, null, 2) + '\n',
);
console.log(`signature: ${signature.length}, callback: ${callback.length}`);
