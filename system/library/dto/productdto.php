<?php

namespace Dto;

/**
 * ProductDTO for Product entity. <br>
 * At the moment used only in {@link ControllerCheckoutCartCheckProducts}.
 */
class ProductDto {
    private ?string $id;
    private ?string $name;
    private ?string $model;
    private ?string $image;
    private ?int $quantity;
    private ?int $stock;
    private ?float $price;

    /**
     * @param ?string $id
     * @param ?string $name
     * @param ?string $model
     * @param ?string $image
     * @param ?int $quantity
     * @param ?int $stock
     * @param ?float $price
     */
    public function __construct(
        ?string $id,
        ?string $name,
        ?string $model,
        ?string $image,
        ?int $quantity,
        ?int $stock,
        ?float $price
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->model = $model;
        $this->image = $image;
        $this->quantity = $quantity;
        $this->stock = $stock;
        $this->price = $price;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function image(): ?string
    {
        return $this->image;
    }

    public function quantity(): ?int
    {
        return $this->quantity;
    }

    public function stock(): ?int
    {
        return $this->stock;
    }

    public function price(): ?float
    {
        return $this->price;
    }
}