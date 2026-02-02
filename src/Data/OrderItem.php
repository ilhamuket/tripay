<?php

namespace Ufrfrk\Tripay\Data;

use InvalidArgumentException;

class OrderItem
{
    protected ?string $sku = null;
    protected string $name;
    protected int $price;
    protected int $quantity;
    protected ?string $productUrl = null;
    protected ?string $imageUrl = null;

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
        if (isset($data['sku'])) {
            $this->setSku($data['sku']);
        }
        if (isset($data['name'])) {
            $this->setName($data['name']);
        }
        if (isset($data['price'])) {
            $this->setPrice($data['price']);
        }
        if (isset($data['quantity'])) {
            $this->setQuantity($data['quantity']);
        }
        if (isset($data['product_url'])) {
            $this->setProductUrl($data['product_url']);
        }
        if (isset($data['image_url'])) {
            $this->setImageUrl($data['image_url']);
        }

        return $this;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setPrice(int $price): self
    {
        if ($price < 1) {
            throw new InvalidArgumentException('Price must be greater than 0');
        }
        $this->price = $price;
        return $this;
    }

    public function setQuantity(int $quantity): self
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be greater than 0');
        }
        $this->quantity = $quantity;
        return $this;
    }

    public function setProductUrl(?string $productUrl): self
    {
        $this->productUrl = $productUrl;
        return $this;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * Get subtotal (price * quantity)
     */
    public function getSubtotal(): int
    {
        return $this->price * $this->quantity;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];

        if ($this->sku) {
            $data['sku'] = $this->sku;
        }

        if ($this->productUrl) {
            $data['product_url'] = $this->productUrl;
        }

        if ($this->imageUrl) {
            $data['image_url'] = $this->imageUrl;
        }

        return $data;
    }
}
