import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  PRODUCTION_URL,
  SANDBOX_URL,
  Tripay,
  TripayApiError,
  TripayConnectionError,
  TripayValidationError,
  parseStatusMessage,
  toFormBody,
  transactionSignature,
} from '../src';
import type { CreateTransactionInput } from '../src';

interface Call {
  url: URL;
  init: RequestInit;
}

function mockFetch(status: number, body: unknown) {
  const calls: Call[] = [];
  const fetchImpl = (async (url: URL, init: RequestInit) => {
    calls.push({ url, init });
    const text = typeof body === 'string' ? body : JSON.stringify(body);
    return new Response(text, { status, headers: { 'Content-Type': 'application/json' } });
  }) as unknown as typeof fetch;
  return { calls, fetchImpl };
}

const config = { apiKey: 'api-key-test', privateKey: 'private-key-sandbox', merchantCode: 'T0001' };
const input: CreateTransactionInput = {
  method: 'BRIVA',
  merchantRef: 'INV-0001',
  amount: 50000,
  customerName: 'Budi',
  customerEmail: 'budi@example.com',
  orderItems: [{ sku: 'SKU-1', name: 'Kaos', price: 25000, quantity: 2 }],
  callbackUrl: 'https://example.com/tripay/callback',
};

test('base URL mengikuti mode', () => {
  assert.equal(new Tripay(config).baseUrl, SANDBOX_URL);
  assert.equal(new Tripay({ ...config, mode: 'production' }).baseUrl, PRODUCTION_URL);
  assert.equal(new Tripay({ ...config, baseUrl: 'https://proxy.local/tripay/' }).baseUrl, 'https://proxy.local/tripay');
});

test('config wajib lengkap', () => {
  assert.throws(() => new Tripay({ ...config, apiKey: '' }), TripayValidationError);
  assert.throws(() => new Tripay({ ...config, privateKey: ' ' }), TripayValidationError);
  assert.throws(() => new Tripay({ ...config, mode: 'live' as never }), TripayValidationError);
});

test('createTransaction: header, form PHP-style, signature', async () => {
  const { calls, fetchImpl } = mockFetch(200, {
    success: true,
    message: '',
    data: { reference: 'DEV-T0001', amount: 50000, status: 'UNPAID' },
  });
  const tx = await new Tripay({ ...config, fetch: fetchImpl }).createTransaction(input);

  assert.equal(tx.reference, 'DEV-T0001');
  assert.equal(calls.length, 1);
  const { url, init } = calls[0];
  assert.equal(url.toString(), `${SANDBOX_URL}/transaction/create`);
  assert.equal(init.method, 'POST');
  const headers = init.headers as Record<string, string>;
  assert.equal(headers.Authorization, 'Bearer api-key-test');
  assert.equal(headers['Content-Type'], 'application/x-www-form-urlencoded');

  const form = init.body as URLSearchParams;
  assert.equal(form.get('order_items[0][name]'), 'Kaos');
  assert.equal(form.get('order_items[0][quantity]'), '2');
  assert.equal(form.get('customer_phone'), null);
  assert.equal(form.get('signature'), transactionSignature('T0001', 'INV-0001', 50000, 'private-key-sandbox'));
  assert.match(form.toString(), /order_items%5B0%5D%5Bname%5D=Kaos/);
});

test('createTransaction: expiresInMinutes jadi unix timestamp', async () => {
  const { calls, fetchImpl } = mockFetch(200, { success: true, data: { reference: 'R', amount: 1, status: 'UNPAID' } });
  const before = Math.floor(Date.now() / 1000);
  await new Tripay({ ...config, fetch: fetchImpl }).createTransaction({ ...input, expiresInMinutes: 60 });
  const expired = Number((calls[0].init.body as URLSearchParams).get('expired_time'));
  assert.ok(expired >= before + 3600 && expired <= before + 3602);
});

test('createTransaction: input tidak valid ditolak sebelum request', async () => {
  const { calls, fetchImpl } = mockFetch(200, {});
  const tripay = new Tripay({ ...config, fetch: fetchImpl });
  for (const bad of [
    { amount: 0 },
    { amount: -1 },
    { amount: 1.5 },
    { amount: Number.NaN },
    { amount: 2 ** 60 },
    { merchantRef: '' },
    { customerEmail: '' },
    { orderItems: [] },
    { orderItems: [{ name: 'X', price: -1, quantity: 1 }] },
    { orderItems: [{ name: 'X', price: 1, quantity: 0 }] },
    { expiresInMinutes: 0 },
  ]) {
    await assert.rejects(tripay.createTransaction({ ...input, ...bad } as CreateTransactionInput), TripayValidationError);
  }
  assert.equal(calls.length, 0);
});

test('error HTTP >= 400 jadi TripayApiError dengan status', async () => {
  const { fetchImpl } = mockFetch(401, { success: false, message: 'Invalid API key' });
  await assert.rejects(new Tripay({ ...config, fetch: fetchImpl }).getPaymentChannels(), (err: unknown) => {
    assert.ok(err instanceof TripayApiError);
    assert.equal(err.status, 401);
    assert.equal(err.message, 'Invalid API key');
    return true;
  });
});

test('success=false walau HTTP 200 tetap error', async () => {
  const { fetchImpl } = mockFetch(200, { success: false, message: 'Reference tidak ditemukan' });
  await assert.rejects(new Tripay({ ...config, fetch: fetchImpl }).getTransactionDetail('X'), TripayApiError);
});

test('respons bukan JSON atau kosong jadi TripayApiError', async () => {
  for (const body of ['<html>502</html>', '']) {
    const { fetchImpl } = mockFetch(502, body);
    await assert.rejects(new Tripay({ ...config, fetch: fetchImpl }).getPaymentChannels(), TripayApiError);
  }
});

test('gagal jaringan/TLS jadi TripayConnectionError tanpa retry', async () => {
  let count = 0;
  const fetchImpl = (async () => {
    count++;
    throw new TypeError('fetch failed: unable to verify the first certificate');
  }) as unknown as typeof fetch;
  await assert.rejects(new Tripay({ ...config, fetch: fetchImpl }).getPaymentChannels(), TripayConnectionError);
  assert.equal(count, 1);
});

test('getTransactions memetakan query dan pagination', async () => {
  const { calls, fetchImpl } = mockFetch(200, {
    success: true,
    data: [{ reference: 'A', amount: 1, status: 'PAID' }],
    pagination: { current_page: 1, last_page: 3 },
  });
  const res = await new Tripay({ ...config, fetch: fetchImpl }).getTransactions({ page: 1, perPage: 50, merchantRef: 'INV' });
  assert.equal(res.data.length, 1);
  assert.equal(res.pagination.last_page, 3);
  const q = calls[0].url.searchParams;
  assert.equal(q.get('per_page'), '50');
  assert.equal(q.get('merchant_ref'), 'INV');
  assert.equal(q.has('status'), false);
});

test('checkTransactionStatus: UNPAID tidak terbaca PAID', async () => {
  assert.equal(parseStatusMessage('Status transaksi saat ini UNPAID'), 'UNPAID');
  assert.equal(parseStatusMessage('Status transaksi saat ini PAID'), 'PAID');
  // Pesan asli sandbox Tripay untuk transaksi lunas
  assert.equal(parseStatusMessage('Status transaksi saat ini DIBAYAR'), 'PAID');
  assert.equal(parseStatusMessage('Status transaksi berhasil DIBAYAR'), 'PAID');
  assert.equal(parseStatusMessage('Belum DIBAYARKAN'), null);
  assert.equal(parseStatusMessage('Status transaksi saat ini expired'), 'EXPIRED');
  assert.equal(parseStatusMessage('Tidak dikenal'), null);

  const { fetchImpl } = mockFetch(200, { success: true, message: 'Status transaksi saat ini UNPAID' });
  const res = await new Tripay({ ...config, fetch: fetchImpl }).checkTransactionStatus('DEV-1');
  assert.deepEqual(res, { message: 'Status transaksi saat ini UNPAID', status: 'UNPAID' });
});

test('getPaymentInstruction: allow_html default 1', async () => {
  const { calls, fetchImpl } = mockFetch(200, { success: true, data: [{ title: 'ATM', steps: ['a'] }] });
  const tripay = new Tripay({ ...config, fetch: fetchImpl });
  await tripay.getPaymentInstruction({ code: 'BRIVA' });
  await tripay.getPaymentInstruction({ code: 'BRIVA', allowHtml: false, payCode: '123' });
  assert.equal(calls[0].url.searchParams.get('allow_html'), '1');
  assert.equal(calls[1].url.searchParams.get('allow_html'), '0');
  assert.equal(calls[1].url.searchParams.get('pay_code'), '123');
});

test('parseCallback: valid, signature salah, JSON rusak', () => {
  const tripay = new Tripay(config);
  const body = JSON.stringify({ reference: 'DEV-1', merchant_ref: 'INV-0001', status: 'PAID' });
  const sig = require('node:crypto').createHmac('sha256', config.privateKey).update(body).digest('hex');

  assert.equal(tripay.parseCallback(body, sig).status, 'PAID');
  assert.throws(() => tripay.parseCallback(body, 'x'.repeat(64)), TripayValidationError);

  const broken = '{not json';
  const brokenSig = require('node:crypto').createHmac('sha256', config.privateKey).update(broken).digest('hex');
  assert.throws(() => tripay.parseCallback(broken, brokenSig), TripayValidationError);
});

test('toFormBody melewati null/undefined dan boolean jadi 1/0', () => {
  const form = toFormBody({ a: undefined, b: null, c: true, d: { e: [1, 2] } });
  assert.equal(form.toString(), 'c=1&d%5Be%5D%5B0%5D=1&d%5Be%5D%5B1%5D=2');
});
