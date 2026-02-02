<?php

namespace Ilhamuket\Tripay\Data;

use InvalidArgumentException;

class TransactionData
{
    protected string $method;
    protected string $merchantRef;
    protected int $amount;
    protected string $customerName;
    protected string $customerEmail;
    protected ?string $customerPhone = null;
    protected array $orderItems = [];
    protected ?string $callbackUrl = null;
    protected ?string $returnUrl = null;
    protected ?int $expiredTime = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Create new instance from array
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Fill data from array
     */
    public function fill(array $data): self
    {
        if (isset($data['method'])) {
            $this->setMethod($data['method']);
        }
        if (isset($data['merchant_ref'])) {
            $this->setMerchantRef($data['merchant_ref']);
        }
        if (isset($data['amount'])) {
            $this->setAmount($data['amount']);
        }
        if (isset($data['customer_name'])) {
            $this->setCustomerName($data['customer_name']);
        }
        if (isset($data['customer_email'])) {
            $this->setCustomerEmail($data['customer_email']);
        }
        if (isset($data['customer_phone'])) {
            $this->setCustomerPhone($data['customer_phone']);
        }
        if (isset($data['order_items'])) {
            $this->setOrderItems($data['order_items']);
        }
        if (isset($data['callback_url'])) {
            $this->setCallbackUrl($data['callback_url']);
        }
        if (isset($data['return_url'])) {
            $this->setReturnUrl($data['return_url']);
        }
        if (isset($data['expired_time'])) {
            $this->setExpiredTime($data['expired_time']);
        }

        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setMerchantRef(string $merchantRef): self
    {
        $this->merchantRef = $merchantRef;
        return $this;
    }

    public function setAmount(int $amount): self
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
        return $this;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function setCustomerPhone(?string $customerPhone): self
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function setOrderItems(array $orderItems): self
    {
        $this->orderItems = [];
        foreach ($orderItems as $item) {
            $this->addOrderItem($item);
        }
        return $this;
    }

    public function addOrderItem(array|OrderItem $item): self
    {
        if (is_array($item)) {
            $item = new OrderItem($item);
        }
        $this->orderItems[] = $item;
        return $this;
    }

    public function setCallbackUrl(?string $callbackUrl): self
    {
        $this->callbackUrl = $callbackUrl;
        return $this;
    }

    public function setReturnUrl(?string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    public function setExpiredTime(?int $expiredTime): self
    {
        $this->expiredTime = $expiredTime;
        return $this;
    }

    /**
     * Set expiry in hours from now
     */
    public function setExpiryHours(int $hours): self
    {
        $this->expiredTime = time() + ($hours * 60 * 60);
        return $this;
    }

    /**
     * Set expiry in minutes from now
     */
    public function setExpiryMinutes(int $minutes): self
    {
        $this->expiredTime = time() + ($minutes * 60);
        return $this;
    }

    /**
     * Validate required fields
     */
    public function validate(): bool
    {
        $required = ['method', 'merchantRef', 'amount', 'customerName', 'customerEmail'];
        
        foreach ($required as $field) {
            if (empty($this->$field)) {
                throw new InvalidArgumentException("Field {$field} is required");
            }
        }

        if (empty($this->orderItems)) {
            throw new InvalidArgumentException('At least one order item is required');
        }

        return true;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $this->validate();

        $data = [
            'method' => $this->method,
            'merchant_ref' => $this->merchantRef,
            'amount' => $this->amount,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'order_items' => array_map(fn($item) => $item->toArray(), $this->orderItems),
        ];

        if ($this->customerPhone) {
            $data['customer_phone'] = $this->customerPhone;
        }

        if ($this->callbackUrl) {
            $data['callback_url'] = $this->callbackUrl;
        }

        if ($this->returnUrl) {
            $data['return_url'] = $this->returnUrl;
        }

        if ($this->expiredTime) {
            $data['expired_time'] = $this->expiredTime;
        }

        return $data;
    }
}
