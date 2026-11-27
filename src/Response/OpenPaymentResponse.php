<?php

namespace Ilhamuket\Tripay\Response;

/**
 * Respons create dan detail Open Payment.
 */
class OpenPaymentResponse extends BaseResponse
{
    public function getUuid(): string
    {
        return $this->data['uuid'] ?? '';
    }

    public function getMerchantRef(): ?string
    {
        return $this->data['merchant_ref'] ?? null;
    }

    public function getCustomerName(): ?string
    {
        return $this->data['customer_name'] ?? null;
    }

    public function getPaymentMethod(): string
    {
        return $this->data['payment_method'] ?? '';
    }

    public function getPaymentName(): string
    {
        return $this->data['payment_name'] ?? '';
    }

    /**
     * Nomor VA / kode bayar (null untuk QRIS).
     */
    public function getPayCode(): ?string
    {
        return $this->data['pay_code'] ?? null;
    }

    public function getQrString(): ?string
    {
        return $this->data['qr_string'] ?? null;
    }

    public function getQrUrl(): ?string
    {
        return $this->data['qr_url'] ?? null;
    }
}
