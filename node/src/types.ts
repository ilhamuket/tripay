/** Kode channel. Daftar `KnownChannelCode` bisa bertambah, jadi string lain tetap diterima. */
export type KnownChannelCode =
  | 'ALFAMART' | 'ALFAMIDI' | 'BCAVA' | 'BNIVA' | 'BRIVA' | 'BSIVA' | 'CIMBVA' | 'DANA'
  | 'INDOMARET' | 'MANDIRIVA' | 'MUAMALATVA' | 'MYBVA' | 'OCBCVA' | 'OVO' | 'PERMATAVA'
  | 'QRIS' | 'QRIS2' | 'QRISC' | 'SAMPOERNAVA' | 'SHOPEEPAY' | 'SMSVA';
export type ChannelCode = KnownChannelCode | (string & {});

export type TransactionStatus = 'UNPAID' | 'PAID' | 'EXPIRED' | 'FAILED' | 'REFUND';
export type CallbackStatus = Exclude<TransactionStatus, 'UNPAID'>;

export type TripayMode = 'sandbox' | 'production';

export interface TripayConfig {
  apiKey: string;
  privateKey: string;
  merchantCode: string;
  /** Default `sandbox`. */
  mode?: TripayMode;
  /** Ganti base URL (mis. untuk proxy). Mengalahkan `mode`. */
  baseUrl?: string;
  /** Default 30000 ms. */
  timeoutMs?: number;
  /** Untuk test atau runtime tanpa fetch global. */
  fetch?: typeof fetch;
}

export interface Fee {
  flat?: number;
  /** Tripay kadang mengirim string, mis. "0.00". */
  percent?: number | string;
  min?: number | null;
  max?: number | null;
}

export interface PaymentChannel {
  group?: string;
  code: ChannelCode;
  name: string;
  type?: string;
  fee_merchant?: Fee;
  fee_customer?: Fee;
  total_fee?: Fee;
  minimum_fee?: number | null;
  maximum_fee?: number | null;
  minimum_amount?: number;
  maximum_amount?: number;
  icon_url?: string;
  active?: boolean;
}

export interface FeeCalculation {
  code: ChannelCode;
  name?: string;
  fee?: Fee;
  total_fee?: { merchant?: number; customer?: number };
}

export interface OrderItem {
  sku?: string;
  name: string;
  /** Rupiah, bilangan bulat. */
  price: number;
  quantity: number;
  subtotal?: number;
  product_url?: string;
  image_url?: string;
}

export interface CreateTransactionInput {
  method: ChannelCode;
  merchantRef: string;
  /** Rupiah, bilangan bulat positif. */
  amount: number;
  customerName: string;
  customerEmail: string;
  customerPhone?: string;
  orderItems: OrderItem[];
  callbackUrl?: string;
  returnUrl?: string;
  /** Unix timestamp (detik). Abaikan bila memakai `expiresInMinutes`. */
  expiredTime?: number;
  /** Alternatif `expiredTime`: kedaluwarsa N menit dari sekarang. */
  expiresInMinutes?: number;
}

export interface Instruction {
  title: string;
  steps: string[];
}

export interface Transaction {
  reference: string;
  merchant_ref?: string;
  payment_method?: ChannelCode;
  payment_name?: string;
  payment_selection_type?: string;
  callback_url?: string | null;
  return_url?: string | null;
  customer_name?: string;
  customer_email?: string;
  customer_phone?: string | null;
  amount: number;
  fee_merchant?: number;
  fee_customer?: number;
  total_fee?: number;
  amount_received?: number;
  pay_code?: string | null;
  pay_url?: string | null;
  checkout_url?: string;
  status: TransactionStatus;
  expired_time?: number;
  /** Dokumentasi Tripay: int di daftar transaksi, string di contoh detail. */
  paid_at?: number | string | null;
  /** Hanya di daftar transaksi. */
  note?: string | null;
  created_at?: number;
  expired_at?: number;
  qr_string?: string | null;
  qr_url?: string | null;
  order_items?: OrderItem[];
  instructions?: Instruction[];
}

export interface Pagination {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total_records?: number;
  next_page?: number | null;
  previous_page?: number | null;
  offset?: Record<string, number> | number;
  sort?: string;
}

export interface TransactionListParams {
  page?: number;
  perPage?: number;
  sort?: 'asc' | 'desc';
  reference?: string;
  merchantRef?: string;
  method?: ChannelCode;
  status?: TransactionStatus;
}

export interface TransactionList {
  data: Transaction[];
  pagination: Pagination;
}

export interface CheckStatusResult {
  /** Pesan asli Tripay, mis. "Status transaksi saat ini PAID". */
  message: string;
  /**
   * Status hasil parsing per kata (DIBAYAR dibaca PAID); `null` bila tidak dikenali.
   * Untuk keputusan bisnis, pakai `getTransactionDetail().status` atau callback.
   */
  status: TransactionStatus | null;
}

export interface PaymentInstructionParams {
  code: ChannelCode;
  payCode?: string;
  amount?: number;
  /** Default `true`. */
  allowHtml?: boolean;
}

export interface CallbackPayload {
  reference: string;
  merchant_ref: string | null;
  payment_method: string;
  payment_method_code: ChannelCode;
  total_amount: number;
  fee_merchant: number;
  fee_customer: number;
  total_fee: number;
  amount_received: number;
  is_closed_payment: 0 | 1;
  status: CallbackStatus;
  paid_at: number | null;
  note: string | null;
}
