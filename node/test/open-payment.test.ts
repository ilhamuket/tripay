import { test } from 'node:test';
import assert from 'node:assert/strict';
import { PRODUCTION_URL, Tripay, TripayValidationError, openPaymentSignature } from '../src';

interface Call {
  url: URL;
  init: RequestInit;
}

function mockFetch(body: unknown) {
  const calls: Call[] = [];
  const fetchImpl = (async (url: URL, init: RequestInit) => {
    calls.push({ url, init });
    return new Response(JSON.stringify(body), { status: 200 });
  }) as unknown as typeof fetch;
  return { calls, fetchImpl };
}

const config = { apiKey: 'api-key', privateKey: 'private-key-sandbox', merchantCode: 'T0001', mode: 'production' as const };

test('createOpenPayment: form + signature urutan code+method+ref', async () => {
  const { calls, fetchImpl } = mockFetch({ success: true, data: { uuid: 'T0001-OP-1', qr_string: '000201', pay_code: null } });
  const op = await new Tripay({ ...config, fetch: fetchImpl }).createOpenPayment({ method: 'QRIS', merchantRef: 'OP-0001', customerName: 'Budi' });

  assert.equal(op.uuid, 'T0001-OP-1');
  assert.equal(calls[0].url.toString(), `${PRODUCTION_URL}/open-payment/create`);
  assert.equal(calls[0].init.method, 'POST');
  const form = calls[0].init.body as URLSearchParams;
  assert.equal(form.get('method'), 'QRIS');
  assert.equal(form.get('merchant_ref'), 'OP-0001');
  assert.equal(form.get('customer_name'), 'Budi');
  assert.equal(form.get('signature'), openPaymentSignature('T0001', 'QRIS', 'OP-0001', 'private-key-sandbox'));
});

test('createOpenPayment tanpa merchantRef: field tidak dikirim, signature pakai string kosong', async () => {
  const { calls, fetchImpl } = mockFetch({ success: true, data: { uuid: 'U' } });
  await new Tripay({ ...config, fetch: fetchImpl }).createOpenPayment({ method: 'QRIS' });
  const form = calls[0].init.body as URLSearchParams;
  assert.equal(form.has('merchant_ref'), false);
  assert.equal(form.has('customer_name'), false);
  assert.equal(form.get('signature'), openPaymentSignature('T0001', 'QRIS', '', 'private-key-sandbox'));
});

test('detail dan transactions: uuid di-encode, query dipetakan, pagination dibaca', async () => {
  const { calls, fetchImpl } = mockFetch({
    success: true,
    data: [{ reference: 'T1', amount: 10000, status: 'PAID' }],
    pagination: { total: 3, current_page: 1, last_page: 2, next_page: 2 },
  });
  const tripay = new Tripay({ ...config, fetch: fetchImpl });
  await tripay.getOpenPaymentDetail('T0001-OP-1');
  const list = await tripay.getOpenPaymentTransactions('a/../b', { startDate: '2026-11-01 00:00:00', perPage: 100, merchantRef: 'OP' });

  assert.equal(calls[0].url.pathname, '/api/open-payment/T0001-OP-1/detail');
  assert.equal(calls[1].url.pathname, '/api/open-payment/a%2F..%2Fb/transactions');
  const q = calls[1].url.searchParams;
  assert.equal(q.get('start_date'), '2026-11-01 00:00:00');
  assert.equal(q.get('per_page'), '100');
  assert.equal(q.get('merchant_ref'), 'OP');
  assert.equal(q.has('end_date'), false);
  assert.equal(list.data.length, 1);
  assert.equal(list.pagination.next_page, 2);
});

test('input tidak valid ditolak sebelum request', async () => {
  const { calls, fetchImpl } = mockFetch({});
  const tripay = new Tripay({ ...config, fetch: fetchImpl });
  await assert.rejects(tripay.createOpenPayment({ method: ' ' }), TripayValidationError);
  await assert.rejects(tripay.getOpenPaymentDetail(''), TripayValidationError);
  await assert.rejects(tripay.getOpenPaymentTransactions('U', { perPage: 101 }), TripayValidationError);
  await assert.rejects(tripay.getOpenPaymentTransactions('U', { perPage: 0 }), TripayValidationError);
  await assert.rejects(tripay.getOpenPaymentTransactions('U', { startDate: '2026-11-01' }), TripayValidationError);
  await assert.rejects(tripay.getOpenPaymentTransactions('U', { endDate: '01/11/2026 00:00:00' }), TripayValidationError);
  assert.equal(calls.length, 0);
});

test('mode sandbox ditolak dengan pesan jelas, kecuali baseUrl kustom', async () => {
  const { calls, fetchImpl } = mockFetch({ success: true, data: { uuid: 'U' } });
  const sandbox = new Tripay({ ...config, mode: 'sandbox', fetch: fetchImpl });
  for (const call of [
    () => sandbox.createOpenPayment({ method: 'QRIS' }),
    () => sandbox.getOpenPaymentDetail('U'),
    () => sandbox.getOpenPaymentTransactions('U'),
  ]) {
    await assert.rejects(call(), (err: unknown) => err instanceof TripayValidationError && /production/.test(err.message));
  }
  assert.equal(calls.length, 0);

  const proxied = new Tripay({ ...config, mode: 'sandbox', baseUrl: 'https://proxy.local/tripay', fetch: fetchImpl });
  await proxied.getOpenPaymentDetail('U');
  assert.equal(calls[0].url.toString(), 'https://proxy.local/tripay/open-payment/U/detail');
});
