<?php

namespace Ufrfrk\Tripay\Response;

class TransactionDetailResponse extends BaseResponse
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
    public function getMerchantRef(): ?string
    {
        return $this->data['merchant_ref'] ?? null;
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
        return strtoupper($this->data['status'] ?? '');
    }

    /**
     * Get paid at timestamp
     */
    public function getPaidAt(): ?int
    {
        return isset($this->data['paid_at']) ? (int) $this->data['paid_at'] : null;
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
     * Get expired time
     */
    public function getExpiredTime(): int
    {
        return (int) ($this->data['expired_time'] ?? 0);
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
     * Check if is paid
     */
    public function isPaid(): bool
    {
        return $this->getStatus() === 'PAID';
    }

    /**
     * Check if is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->getStatus() === 'UNPAID';
    }

    /**
     * Check if is expired
     */
    public function isExpired(): bool
    {
        return $this->getStatus() === 'EXPIRED';
    }

    /**
     * Check if is failed
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === 'FAILED';
    }

    /**
     * Check if is refund
     */
    public function isRefund(): bool
    {
        return $this->getStatus() === 'REFUND';
    }
}
