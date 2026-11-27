/**
 * Encode objek bersarang ke form urlencoded gaya PHP `http_build_query`:
 * `{ order_items: [{ name: 'A' }] }` -> `order_items%5B0%5D%5Bname%5D=A`.
 * Nilai `undefined`/`null` dilewati.
 */
export function toFormBody(input: Record<string, unknown>): URLSearchParams {
  const params = new URLSearchParams();
  const walk = (value: unknown, key: string) => {
    if (value === undefined || value === null) return;
    if (Array.isArray(value)) {
      value.forEach((item, i) => walk(item, `${key}[${i}]`));
    } else if (typeof value === 'object') {
      for (const [k, v] of Object.entries(value as Record<string, unknown>)) walk(v, `${key}[${k}]`);
    } else if (typeof value === 'boolean') {
      params.append(key, value ? '1' : '0');
    } else {
      params.append(key, String(value));
    }
  };
  for (const [k, v] of Object.entries(input)) walk(v, k);
  return params;
}
