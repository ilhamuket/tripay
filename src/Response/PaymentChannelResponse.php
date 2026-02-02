<?php

namespace Ufrfrk\Tripay\Response;

use Illuminate\Support\Collection;

class PaymentChannelResponse extends BaseResponse
{
    /**
     * Get all payment channels
     */
    public function getChannels(): Collection
    {
        return collect($this->data ?? [])->map(function ($channel) {
            return new PaymentChannel($channel);
        });
    }

    /**
     * Get channel by code
     */
    public function getChannel(string $code): ?PaymentChannel
    {
        return $this->getChannels()->first(function ($channel) use ($code) {
            return $channel->getCode() === $code;
        });
    }

    /**
     * Get channels by group
     */
    public function getByGroup(string $group): Collection
    {
        return $this->getChannels()->filter(function ($channel) use ($group) {
            return $channel->getGroup() === $group;
        });
    }

    /**
     * Get only active channels
     */
    public function getActiveChannels(): Collection
    {
        return $this->getChannels()->filter(fn($channel) => $channel->isActive());
    }

    /**
     * Get Virtual Account channels
     */
    public function getVirtualAccounts(): Collection
    {
        return $this->getByGroup('Virtual Account');
    }

    /**
     * Get E-Wallet channels
     */
    public function getEWallets(): Collection
    {
        return $this->getByGroup('E-Wallet');
    }

    /**
     * Get Convenience Store channels
     */
    public function getConvenienceStores(): Collection
    {
        return $this->getByGroup('Convenience Store');
    }
}

class PaymentChannel
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getGroup(): string
    {
        return $this->data['group'] ?? '';
    }

    public function getCode(): string
    {
        return $this->data['code'] ?? '';
    }

    public function getName(): string
    {
        return $this->data['name'] ?? '';
    }

    public function getType(): string
    {
        return $this->data['type'] ?? '';
    }

    public function getFeeMerchant(): array
    {
        return $this->data['fee_merchant'] ?? [];
    }

    public function getFeeCustomer(): array
    {
        return $this->data['fee_customer'] ?? [];
    }

    public function getTotalFee(): array
    {
        return $this->data['total_fee'] ?? [];
    }

    public function getMinimumFee(): int
    {
        return (int) ($this->data['minimum_fee'] ?? 0);
    }

    public function getMaximumFee(): int
    {
        return (int) ($this->data['maximum_fee'] ?? 0);
    }

    public function getMinimumAmount(): int
    {
        return (int) ($this->data['minimum_amount'] ?? 0);
    }

    public function getMaximumAmount(): int
    {
        return (int) ($this->data['maximum_amount'] ?? 0);
    }

    public function getIconUrl(): ?string
    {
        return $this->data['icon_url'] ?? null;
    }

    public function isActive(): bool
    {
        return (bool) ($this->data['active'] ?? false);
    }

    /**
     * Calculate fee for given amount
     */
    public function calculateFee(int $amount): int
    {
        $flat = (int) ($this->data['total_fee']['flat'] ?? 0);
        $percent = (float) ($this->data['total_fee']['percent'] ?? 0);
        
        return $flat + (int) ceil($amount * $percent / 100);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
