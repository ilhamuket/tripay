<?php

namespace Ilhamuket\Tripay\Response;

class CheckStatusResponse extends BaseResponse
{
    /**
     * Get status message
     */
    public function getStatusMessage(): string
    {
        return $this->message;
    }

    /**
     * Check if transaction is paid based on message
     */
    public function isPaid(): bool
    {
        // Tripay menulis status lunas sebagai "DIBAYAR" (mis. "Status transaksi saat ini DIBAYAR").
        return $this->statusIs('PAID') || $this->statusIs('DIBAYAR');
    }

    /**
     * Check if transaction is unpaid based on message
     */
    public function isUnpaid(): bool
    {
        return $this->statusIs('UNPAID');
    }

    /**
     * Check if transaction is expired based on message
     */
    public function isExpired(): bool
    {
        return $this->statusIs('EXPIRED');
    }

    /**
     * Cocokkan status per kata. `str_contains('UNPAID', 'PAID')` bernilai true,
     * jadi pencocokan substring membuat transaksi UNPAID terbaca lunas.
     */
    protected function statusIs(string $status): bool
    {
        return preg_match('/\\b' . preg_quote($status, '/') . '\\b/i', $this->message) === 1;
    }
}
