import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { Tripay, openPaymentSignature, transactionSignature, verifyCallbackSignature } from '../src';

// Vector bersama untuk semua bahasa (spec/test-vectors.json).
const vectors = JSON.parse(readFileSync(join(__dirname, '../../../spec/test-vectors.json'), 'utf8')) as {
  signature: Array<{ merchantCode: string; privateKey: string; merchantRef: string; amount: number; expected: string }>;
  openPayment: Array<{ merchantCode: string; privateKey: string; method: string; merchantRef: string; expected: string }>;
  callback: Array<{ name: string; privateKey: string; rawBody: string; signature: string; valid: boolean }>;
};

for (const v of vectors.signature) {
  test(`signature transaksi ${v.merchantRef}`, () => {
    assert.equal(transactionSignature(v.merchantCode, v.merchantRef, v.amount, v.privateKey), v.expected);
    const client = new Tripay({ apiKey: 'k', privateKey: v.privateKey, merchantCode: v.merchantCode });
    assert.equal(client.generateSignature(v.merchantRef, v.amount), v.expected);
  });
}

for (const v of vectors.openPayment) {
  test(`signature open payment ${v.method} "${v.merchantRef}"`, () => {
    assert.equal(openPaymentSignature(v.merchantCode, v.method, v.merchantRef, v.privateKey), v.expected);
    const client = new Tripay({ apiKey: 'k', privateKey: v.privateKey, merchantCode: v.merchantCode });
    assert.equal(client.generateOpenPaymentSignature(v.method, v.merchantRef), v.expected);
  });
}

for (const v of vectors.callback) {
  test(`callback: ${v.name}`, () => {
    assert.equal(verifyCallbackSignature(v.rawBody, v.signature, v.privateKey), v.valid);
    assert.equal(verifyCallbackSignature(Buffer.from(v.rawBody), v.signature, v.privateKey), v.valid);
  });
}

test('callback: signature undefined/null ditolak', () => {
  const v = vectors.callback[0];
  assert.equal(verifyCallbackSignature(v.rawBody, undefined, v.privateKey), false);
  assert.equal(verifyCallbackSignature(v.rawBody, null, v.privateKey), false);
});

test('callback: signature huruf besar tetap diterima', () => {
  const v = vectors.callback[0];
  assert.equal(verifyCallbackSignature(v.rawBody, v.signature.toUpperCase(), v.privateKey), true);
});
