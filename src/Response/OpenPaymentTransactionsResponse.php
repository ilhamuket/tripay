<?php

namespace Ilhamuket\Tripay\Response;

use Illuminate\Support\Collection;

/**
 * Daftar pembayaran yang masuk ke satu Open Payment.
 */
class OpenPaymentTransactionsResponse extends BaseResponse
{
    public function getTransactions(): Collection
    {
        return collect($this->data ?? []);
    }

    /**
     * Bentuk: total, data_from, data_to, per_page, current_page, last_page, next_page.
     */
    public function getPagination(): array
    {
        return $this->rawResponse['pagination'] ?? [];
    }

    public function getTotal(): int
    {
        return (int) ($this->getPagination()['total'] ?? 0);
    }

    public function getCurrentPage(): int
    {
        return (int) ($this->getPagination()['current_page'] ?? 1);
    }

    public function getLastPage(): int
    {
        return (int) ($this->getPagination()['last_page'] ?? 1);
    }

    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }

    public function getNextPage(): ?int
    {
        $next = $this->getPagination()['next_page'] ?? null;

        return $next === null ? null : (int) $next;
    }
}
