<?php

namespace Ufrfrk\Tripay\Response;

abstract class BaseResponse
{
    protected array $rawResponse;
    protected bool $success;
    protected string $message;
    protected mixed $data;

    public function __construct(array $response)
    {
        $this->rawResponse = $response;
        $this->success = $response['success'] ?? false;
        $this->message = $response['message'] ?? '';
        $this->data = $response['data'] ?? null;
    }

    /**
     * Check if request was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get response message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get raw data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get raw response array
     */
    public function toArray(): array
    {
        return $this->rawResponse;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->rawResponse, JSON_PRETTY_PRINT);
    }
}
