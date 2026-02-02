<?php

namespace Ufrfrk\Tripay\Response;

class CreateTransactionResponse extends BaseResponse
{
    /**
     * Get Tripay reference number
     */
    public function getReference(): string
    {
        return $this->data['reference'] ?? '';
    }

    /**
     * Get merchant reference
     */
    public function getMerchantRef(): string
    {
        return $this->data['merchant_ref'] ?? '';
    }

    /**
     * Get payment method code
     */
    public function getPaymentMethod(): string
    {
        return $this->data['payment_method'] ?? '';
    }

    /**
     * Get payment method name
     */
    public function getPaymentName(): string
    {
        return $this->data['payment_name'] ?? '';
    }

    /**
     * Get customer name
     */
    public function getCustomerName(): string
    {
        return $this->data['customer_name'] ?? '';
    }

    /**
     * Get customer email
     */
    public function getCustomerEmail(): string
    {
        return $this->data['customer_email'] ?? '';
    }

    /**
     * Get customer phone
     */
    public function getCustomerPhone(): ?string
    {
        return $this->data['customer_phone'] ?? null;
    }

    /**
     * Get transaction amount
     */
    public function getAmount(): int
    {
        return (int) ($this->data['amount'] ?? 0);
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
     * Get amount received
     */
    public function getAmountReceived(): int
    {
        return (int) ($this->data['amount_received'] ?? 0);
    }

    /**
     * Get pay code (VA number, etc)
     */
    public function getPayCode(): ?string
    {
        return $this->data['pay_code'] ?? null;
    }

    /**
     * Get pay URL
     */
    public function getPayUrl(): ?string
    {
        return $this->data['pay_url'] ?? null;
    }

    /**
     * Get checkout URL
     */
    public function getCheckoutUrl(): ?string
    {
        return $this->data['checkout_url'] ?? null;
    }

    /**
     * Get transaction status
     */
    public function getStatus(): string
    {
        return $this->data['status'] ?? '';
    }

    /**
     * Get expired time as timestamp
     */
    public function getExpiredTime(): int
    {
        return (int) ($this->data['expired_time'] ?? 0);
    }

    /**
     * Get expired time as DateTime
     */
    public function getExpiredAt(): \DateTimeInterface
    {
        return (new \DateTimeImmutable())->setTimestamp($this->getExpiredTime());
    }

    /**
     * Get QR string (for QRIS)
     */
    public function getQrString(): ?string
    {
        return $this->data['qr_string'] ?? null;
    }

    /**
     * Get QR URL (for QRIS)
     */
    public function getQrUrl(): ?string
    {
        return $this->data['qr_url'] ?? null;
    }

    /**
     * Get order items
     */
    public function getOrderItems(): array
    {
        return $this->data['order_items'] ?? [];
    }

    /**
     * Get payment instructions
     */
    public function getInstructions(): array
    {
        return $this->data['instructions'] ?? [];
    }

    /**
     * Check if is QRIS payment
     */
    public function isQris(): bool
    {
        return $this->getPaymentMethod() === 'QRIS';
    }

    /**
     * Check if is Virtual Account payment
     */
    public function isVirtualAccount(): bool
    {
        return str_ends_with($this->getPaymentMethod(), 'VA');
    }
}
