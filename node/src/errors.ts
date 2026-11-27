export class TripayError extends Error {
  constructor(message: string, options?: { cause?: unknown }) {
    super(message, options);
    this.name = new.target.name;
  }
}

/** Tripay membalas error (HTTP >= 400, `success: false`, atau body bukan JSON). */
export class TripayApiError extends TripayError {
  constructor(
    message: string,
    readonly status: number,
    readonly body?: unknown,
  ) {
    super(message);
  }
}

/** Gagal terhubung ke Tripay (DNS, TLS, timeout, jaringan). TLS tidak pernah dimatikan otomatis. */
export class TripayConnectionError extends TripayError {}

/** Input tidak valid sebelum request dikirim, atau signature callback tidak cocok. */
export class TripayValidationError extends TripayError {}
