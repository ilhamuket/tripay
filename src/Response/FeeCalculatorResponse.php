<?php

namespace Ilhamuket\Tripay\Response;

use Illuminate\Support\Collection;

class FeeCalculatorResponse extends BaseResponse
{
    /**
     * Get all fee calculations
     */
    public function getFees(): Collection
    {
        return collect($this->data ?? []);
    }

    /**
     * Get fee for specific channel
     */
    public function getFee(string $code): ?array
    {
        return $this->getFees()->firstWhere('code', $code);
    }

    /**
     * Get total merchant fee for channel
     */
    public function getMerchantFee(string $code): int
    {
        $fee = $this->getFee($code);
        return (int) ($fee['total_fee']['merchant'] ?? 0);
    }

    /**
     * Get total customer fee for channel
     */
    public function getCustomerFee(string $code): int
    {
        $fee = $this->getFee($code);
        return (int) ($fee['total_fee']['customer'] ?? 0);
    }
}
