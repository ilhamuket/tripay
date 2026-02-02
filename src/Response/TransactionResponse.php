<?php

namespace Ufrfrk\Tripay\Response;

use Illuminate\Support\Collection;

class TransactionResponse extends BaseResponse
{
    /**
     * Get all transactions
     */
    public function getTransactions(): Collection
    {
        return collect($this->data ?? []);
    }

    /**
     * Get pagination info
     */
    public function getPagination(): array
    {
        return $this->rawResponse['pagination'] ?? [];
    }

    /**
     * Get current page
     */
    public function getCurrentPage(): int
    {
        return (int) ($this->getPagination()['current_page'] ?? 1);
    }

    /**
     * Get last page
     */
    public function getLastPage(): int
    {
        return (int) ($this->getPagination()['last_page'] ?? 1);
    }

    /**
     * Get per page count
     */
    public function getPerPage(): int
    {
        return (int) ($this->getPagination()['per_page'] ?? 25);
    }

    /**
     * Get total records
     */
    public function getTotalRecords(): int
    {
        return (int) ($this->getPagination()['total_records'] ?? 0);
    }

    /**
     * Check if has more pages
     */
    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }

    /**
     * Get next page number
     */
    public function getNextPage(): ?int
    {
        return $this->getPagination()['next_page'] ?? null;
    }

    /**
     * Get previous page number
     */
    public function getPreviousPage(): ?int
    {
        return $this->getPagination()['previous_page'] ?? null;
    }
}
