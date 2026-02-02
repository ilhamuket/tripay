<?php

namespace ilhamuket\Tripay\Callback;

class CallbackData
{
    protected array $data;

    public const STATUS_PAID = 'PAID';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_REFUND = 'REFUND';

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get raw data array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get Tripay reference number
     */
    public function getReference(): string
    {
        return $this->data['reference'] ?? '';
    }

    /**
     * Get merchant reference (your invoice number)
     */
    public function getMerchantRef(): ?string
    {
        return $this->data['merchant_ref'] ?? null;
    }

    /**
     * Get payment method name
     */
    public function getPaymentMethod(): string
    {
        return $this->data['payment_method'] ?? '';
    }

    /**
     * Get payment method code
     */
    public function getPaymentMethodCode(): string
    {
        return $this->data['payment_method_code'] ?? '';
    }

    /**
     * Get total amount paid by customer
     */
    public function getTotalAmount(): int
    {
        return (int) ($this->data['total_amount'] ?? 0);
    }

    /**
     * Get merchant fee
     */
    public function getFeeMerchant(): int
    {
        return (int) ($this->data['fee_merchant'] ?? 0);
    }

    /**
     * Get customer fee
     */
    public function getFeeCustomer(): int
    {
        return (int) ($this->data['fee_customer'] ?? 0);
    }

    /**
     * Get total fee
     */
    public function getTotalFee(): int
    {
        return (int) ($this->data['total_fee'] ?? 0);
    }

    /**
     * Get amount received by merchant
     */
    public function getAmountReceived(): int
    {
        return (int) ($this->data['amount_received'] ?? 0);
    }

    /**
     * Check if closed payment
     */
    public function isClosedPayment(): bool
    {
        return (int) ($this->data['is_closed_payment'] ?? 0) === 1;
    }

    /**
     * Get payment status
     */
    public function getStatus(): string
    {
        return strtoupper($this->data['status'] ?? '');
    }

    /**
     * Get paid at timestamp
     */
    public function getPaidAt(): ?int
    {
        return $this->data['paid_at'] ?? null;
    }

    /**
     * Get paid at as DateTime
     */
    public function getPaidAtDateTime(): ?\DateTimeInterface
    {
        $paidAt = $this->getPaidAt();
        return $paidAt ? (new \DateTimeImmutable())->setTimestamp($paidAt) : null;
    }

    /**
     * Get note
     */
    public function getNote(): ?string
    {
        return $this->data['note'] ?? null;
    }

    /**
     * Check if payment is successful
     */
    public function isPaid(): bool
    {
        return $this->getStatus() === self::STATUS_PAID;
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->getStatus() === self::STATUS_EXPIRED;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAILED;
    }

    /**
     * Check if payment is refunded
     */
    public function isRefund(): bool
    {
        return $this->getStatus() === self::STATUS_REFUND;
    }
}
