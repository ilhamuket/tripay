<?php

namespace ilhamuket\Tripay\Response;

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
        return str_contains(strtoupper($this->message), 'PAID');
    }

    /**
     * Check if transaction is unpaid based on message
     */
    public function isUnpaid(): bool
    {
        return str_contains(strtoupper($this->message), 'UNPAID');
    }

    /**
     * Check if transaction is expired based on message
     */
    public function isExpired(): bool
    {
        return str_contains(strtoupper($this->message), 'EXPIRED');
    }
}
