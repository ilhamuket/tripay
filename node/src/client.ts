import { TripayApiError, TripayConnectionError, TripayValidationError } from './errors';
import { toFormBody } from './form';
import { openPaymentSignature, transactionSignature, verifyCallbackSignature } from './signature';
import type {
  CallbackPayload,
  ChannelCode,
  CheckStatusResult,
  CreateOpenPaymentInput,
  CreateTransactionInput,
  FeeCalculation,
  OpenPayment,
  OpenPaymentTransactionList,
  OpenPaymentTransactionParams,
  Instruction,
  PaymentChannel,
  PaymentInstructionParams,
  Transaction,
  TransactionList,
  TransactionListParams,
  TransactionStatus,
  TripayConfig,
  TripayMode,
} from './types';

export const SANDBOX_URL = 'https://tripay.co.id/api-sandbox';
export const PRODUCTION_URL = 'https://tripay.co.id/api';
const USER_AGENT = 'ilhamuket-tripay-node/0.2.1';

type Query = Record<string, string | number | undefined | null>;
interface Envelope<T> {
  success?: boolean;
  message?: string;
  data?: T;
  pagination?: unknown;
}

const DATE_TIME = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;

function assertPositiveInt(value: unknown, field: string): asserts value is number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value <= 0) {
    throw new TripayValidationError(`${field} harus bilangan bulat positif`);
  }
}

function assertNonEmpty(value: unknown, field: string): asserts value is string {
  if (typeof value !== 'string' || value.trim() === '') {
    throw new TripayValidationError(`${field} wajib diisi`);
  }
}

/** Kata di pesan check-status yang sudah terlihat di API Tripay. Lunas ditulis "DIBAYAR". */
const STATUS_WORDS: Record<string, TransactionStatus> = {
  UNPAID: 'UNPAID',
  PAID: 'PAID',
  DIBAYAR: 'PAID',
  EXPIRED: 'EXPIRED',
  FAILED: 'FAILED',
  REFUND: 'REFUND',
};

/**
 * Ambil status dari pesan check-status per kata, jadi "UNPAID" tidak terbaca "PAID".
 * Contoh pesan asli: "Status transaksi saat ini UNPAID", "Status transaksi saat ini DIBAYAR".
 * Kata lain menghasilkan `null`. Untuk keputusan bisnis, pakai `getTransactionDetail().status` atau callback.
 */
export function parseStatusMessage(message: string): TransactionStatus | null {
  const match = message.toUpperCase().match(/\b(UNPAID|PAID|DIBAYAR|EXPIRED|FAILED|REFUND)\b/);
  return match ? STATUS_WORDS[match[1]] : null;
}

export class Tripay {
  readonly mode: TripayMode;
  readonly baseUrl: string;
  readonly merchantCode: string;
  private readonly apiKey: string;
  private readonly privateKey: string;
  private readonly timeoutMs: number;
  private readonly fetchImpl: typeof fetch;
  private readonly customBaseUrl: boolean;

  constructor(config: TripayConfig) {
    assertNonEmpty(config?.apiKey, 'apiKey');
    assertNonEmpty(config.privateKey, 'privateKey');
    assertNonEmpty(config.merchantCode, 'merchantCode');
    this.apiKey = config.apiKey;
    this.privateKey = config.privateKey;
    this.merchantCode = config.merchantCode;
    this.mode = config.mode ?? 'sandbox';
    if (this.mode !== 'sandbox' && this.mode !== 'production') {
      throw new TripayValidationError(`mode harus 'sandbox' atau 'production'`);
    }
    this.customBaseUrl = Boolean(config.baseUrl);
    this.baseUrl = (config.baseUrl ?? (this.mode === 'production' ? PRODUCTION_URL : SANDBOX_URL)).replace(/\/+$/, '');
    this.timeoutMs = config.timeoutMs ?? 30_000;
    const f = config.fetch ?? globalThis.fetch;
    if (typeof f !== 'function') throw new TripayValidationError('fetch tidak tersedia, pakai Node >= 18 atau isi config.fetch');
    this.fetchImpl = f;
  }

  get isSandbox(): boolean {
    return this.mode === 'sandbox';
  }

  // ---- Endpoint ----------------------------------------------------------

  async getPaymentChannels(code?: ChannelCode): Promise<PaymentChannel[]> {
    return (await this.get<PaymentChannel[]>('merchant/payment-channel', { code })).data ?? [];
  }

  async calculateFee(amount: number, code?: ChannelCode): Promise<FeeCalculation[]> {
    assertPositiveInt(amount, 'amount');
    return (await this.get<FeeCalculation[]>('merchant/fee-calculator', { amount, code })).data ?? [];
  }

  async getTransactions(params: TransactionListParams = {}): Promise<TransactionList> {
    if (params.page !== undefined) assertPositiveInt(params.page, 'page');
    if (params.perPage !== undefined) assertPositiveInt(params.perPage, 'perPage');
    const res = await this.get<Transaction[]>('merchant/transactions', {
      page: params.page,
      per_page: params.perPage,
      sort: params.sort,
      reference: params.reference,
      merchant_ref: params.merchantRef,
      method: params.method,
      status: params.status,
    });
    return { data: res.data ?? [], pagination: (res.pagination as TransactionList['pagination']) ?? {} };
  }

  async createTransaction(input: CreateTransactionInput): Promise<Transaction> {
    assertNonEmpty(input?.method, 'method');
    assertNonEmpty(input.merchantRef, 'merchantRef');
    assertPositiveInt(input.amount, 'amount');
    assertNonEmpty(input.customerName, 'customerName');
    assertNonEmpty(input.customerEmail, 'customerEmail');
    if (!Array.isArray(input.orderItems) || input.orderItems.length === 0) {
      throw new TripayValidationError('orderItems minimal 1 item');
    }
    input.orderItems.forEach((item, i) => {
      assertNonEmpty(item?.name, `orderItems[${i}].name`);
      if (!Number.isSafeInteger(item.price) || item.price < 0) {
        throw new TripayValidationError(`orderItems[${i}].price harus bilangan bulat >= 0`);
      }
      assertPositiveInt(item.quantity, `orderItems[${i}].quantity`);
    });

    let expiredTime = input.expiredTime;
    if (input.expiresInMinutes !== undefined) {
      assertPositiveInt(input.expiresInMinutes, 'expiresInMinutes');
      expiredTime = Math.floor(Date.now() / 1000) + input.expiresInMinutes * 60;
    } else if (expiredTime !== undefined) {
      assertPositiveInt(expiredTime, 'expiredTime');
    }

    const body = toFormBody({
      method: input.method,
      merchant_ref: input.merchantRef,
      amount: input.amount,
      customer_name: input.customerName,
      customer_email: input.customerEmail,
      customer_phone: input.customerPhone,
      order_items: input.orderItems,
      callback_url: input.callbackUrl,
      return_url: input.returnUrl,
      expired_time: expiredTime,
      signature: this.generateSignature(input.merchantRef, input.amount),
    });
    return this.unwrap(await this.request<Transaction>('POST', 'transaction/create', undefined, body));
  }

  async getTransactionDetail(reference: string): Promise<Transaction> {
    assertNonEmpty(reference, 'reference');
    return this.unwrap(await this.get<Transaction>('transaction/detail', { reference }));
  }

  async checkTransactionStatus(reference: string): Promise<CheckStatusResult> {
    assertNonEmpty(reference, 'reference');
    const res = await this.get<unknown>('transaction/check-status', { reference });
    const message = res.message ?? '';
    return { message, status: parseStatusMessage(message) };
  }

  async getPaymentInstruction(params: PaymentInstructionParams): Promise<Instruction[]> {
    assertNonEmpty(params?.code, 'code');
    if (params.amount !== undefined) assertPositiveInt(params.amount, 'amount');
    const res = await this.get<Instruction[]>('payment/instruction', {
      code: params.code,
      pay_code: params.payCode,
      amount: params.amount,
      allow_html: params.allowHtml === false ? 0 : 1,
    });
    return res.data ?? [];
  }

  // ---- Open Payment (hanya production) -----------------------------------

  /** Buat kode bayar / QRIS yang bisa dibayar berulang kali. Sandbox Tripay tidak menyediakan endpoint ini. */
  async createOpenPayment(input: CreateOpenPaymentInput): Promise<OpenPayment> {
    this.assertOpenPaymentAvailable();
    assertNonEmpty(input?.method, 'method');
    const merchantRef = input.merchantRef ?? '';
    const body = toFormBody({
      method: input.method,
      merchant_ref: merchantRef || undefined,
      customer_name: input.customerName || undefined,
      signature: this.generateOpenPaymentSignature(input.method, merchantRef),
    });
    return this.unwrap(await this.request<OpenPayment>('POST', 'open-payment/create', undefined, body));
  }

  async getOpenPaymentDetail(uuid: string): Promise<OpenPayment> {
    this.assertOpenPaymentAvailable();
    return this.unwrap(await this.get<OpenPayment>(`open-payment/${this.encodeUuid(uuid)}/detail`, {}));
  }

  async getOpenPaymentTransactions(uuid: string, params: OpenPaymentTransactionParams = {}): Promise<OpenPaymentTransactionList> {
    this.assertOpenPaymentAvailable();
    const path = `open-payment/${this.encodeUuid(uuid)}/transactions`;
    if (params.perPage !== undefined) {
      assertPositiveInt(params.perPage, 'perPage');
      if (params.perPage > 100) throw new TripayValidationError('perPage maksimal 100');
    }
    if (params.page !== undefined) assertPositiveInt(params.page, 'page');
    for (const [field, value] of [['startDate', params.startDate], ['endDate', params.endDate]] as const) {
      if (value !== undefined && !DATE_TIME.test(value)) {
        throw new TripayValidationError(`${field} harus berformat "Y-m-d H:i:s"`);
      }
    }
    const res = await this.get<OpenPaymentTransactionList['data']>(path, {
      reference: params.reference,
      merchant_ref: params.merchantRef,
      start_date: params.startDate,
      end_date: params.endDate,
      per_page: params.perPage,
      page: params.page,
    });
    return { data: res.data ?? [], pagination: (res.pagination as OpenPaymentTransactionList['pagination']) ?? {} };
  }

  /** HMAC-SHA256(merchantCode + method + merchantRef, privateKey). Urutannya beda dari closed payment. */
  generateOpenPaymentSignature(method: string, merchantRef = ''): string {
    assertNonEmpty(method, 'method');
    return openPaymentSignature(this.merchantCode, method, merchantRef, this.privateKey);
  }

  private assertOpenPaymentAvailable(): void {
    if (this.mode === 'sandbox' && !this.customBaseUrl) {
      throw new TripayValidationError('Open Payment hanya tersedia di mode production (sandbox Tripay tidak menyediakan endpoint ini)');
    }
  }

  private encodeUuid(uuid: string): string {
    assertNonEmpty(uuid, 'uuid');
    return encodeURIComponent(uuid.trim());
  }

  // ---- Signature & callback ---------------------------------------------

  generateSignature(merchantRef: string, amount: number): string {
    assertNonEmpty(merchantRef, 'merchantRef');
    assertPositiveInt(amount, 'amount');
    return transactionSignature(this.merchantCode, merchantRef, amount, this.privateKey);
  }

  /** `true` bila `X-Callback-Signature` cocok dengan raw body. */
  verifyCallback(rawBody: string | Buffer, signature: string | undefined | null): boolean {
    return verifyCallbackSignature(rawBody, signature, this.privateKey);
  }

  /** Verifikasi lalu parse callback. Melempar `TripayValidationError` bila signature atau JSON tidak valid. */
  parseCallback(rawBody: string | Buffer, signature: string | undefined | null): CallbackPayload {
    if (!this.verifyCallback(rawBody, signature)) {
      throw new TripayValidationError('Signature callback Tripay tidak valid');
    }
    try {
      const data = JSON.parse(rawBody.toString()) as CallbackPayload;
      if (!data || typeof data !== 'object' || typeof data.reference !== 'string') throw new Error('bukan objek callback');
      return data;
    } catch (err) {
      throw new TripayValidationError('Body callback Tripay bukan JSON yang valid', { cause: err });
    }
  }

  // ---- HTTP --------------------------------------------------------------

  private get<T>(path: string, query: Query) {
    return this.request<T>('GET', path, query);
  }

  private unwrap<T>(res: Envelope<T>): T {
    if (res.data === undefined || res.data === null) {
      throw new TripayApiError(res.message || 'Respons Tripay tanpa data', 200, res);
    }
    return res.data;
  }

  private async request<T>(method: 'GET' | 'POST', path: string, query?: Query, body?: URLSearchParams): Promise<Envelope<T>> {
    const url = new URL(`${this.baseUrl}/${path}`);
    for (const [k, v] of Object.entries(query ?? {})) {
      if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, String(v));
    }

    let response: Response;
    try {
      response = await this.fetchImpl(url, {
        method,
        headers: {
          Authorization: `Bearer ${this.apiKey}`,
          Accept: 'application/json',
          'User-Agent': USER_AGENT,
          ...(body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}),
        },
        body,
        signal: AbortSignal.timeout(this.timeoutMs),
      });
    } catch (err) {
      const reason = err instanceof Error ? err.message : String(err);
      throw new TripayConnectionError(`Gagal terhubung ke Tripay: ${reason}`, { cause: err });
    }

    const text = (await response.text()).trim();
    let json: Envelope<T>;
    try {
      json = JSON.parse(text) as Envelope<T>;
      if (!json || typeof json !== 'object') throw new Error('bukan objek');
    } catch {
      throw new TripayApiError(
        text ? `Respons Tripay bukan JSON: ${text.slice(0, 200)}` : 'Respons Tripay kosong',
        response.status,
        text,
      );
    }

    if (!response.ok || json.success === false) {
      throw new TripayApiError(json.message || `Tripay HTTP ${response.status}`, response.status, json);
    }
    return json;
  }
}
