<?php

namespace Ilhamuket\Tripay\Response;

use Illuminate\Support\Collection;

class InstructionResponse extends BaseResponse
{
    /**
     * Get all instructions
     */
    public function getInstructions(): Collection
    {
        return collect($this->data ?? []);
    }

    /**
     * Get instruction by title
     */
    public function getByTitle(string $title): ?array
    {
        return $this->getInstructions()->firstWhere('title', $title);
    }

    /**
     * Get steps for specific instruction title
     */
    public function getSteps(string $title): array
    {
        $instruction = $this->getByTitle($title);
        return $instruction['steps'] ?? [];
    }

    /**
     * Get all titles
     */
    public function getTitles(): Collection
    {
        return $this->getInstructions()->pluck('title');
    }
}
