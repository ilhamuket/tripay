import { createHmac, timingSafeEqual } from 'node:crypto';

const hmacHex = (data: string | Buffer, key: string) => createHmac('sha256', key).update(data).digest('hex');

/** Signature transaksi: HMAC-SHA256(merchantCode + merchantRef + amount, privateKey), hex. */
export function transactionSignature(
  merchantCode: string,
  merchantRef: string,
  amount: number,
  privateKey: string,
): string {
  return hmacHex(`${merchantCode}${merchantRef}${amount}`, privateKey);
}

/**
 * Cocokkan header `X-Callback-Signature` dengan HMAC-SHA256 dari raw body.
 * `rawBody` wajib body asli dari request, bukan hasil `JSON.stringify` ulang.
 */
export function verifyCallbackSignature(
  rawBody: string | Buffer,
  signature: string | undefined | null,
  privateKey: string,
): boolean {
  if (!signature || typeof signature !== 'string') return false;
  const expected = Buffer.from(hmacHex(rawBody, privateKey), 'utf8');
  const given = Buffer.from(signature.trim().toLowerCase(), 'utf8');
  return expected.length === given.length && timingSafeEqual(expected, given);
}
