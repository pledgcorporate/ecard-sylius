<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;

class OrderItemBuilder
{
    /** @var string */
    private $variantCode;

    /** @var string */
    private $variantName;

    /** @var bool */
    private $isShippingRequired;

    /** @var int */
    private $quantity;

    /** @var int */
    private $unitPrice;

    public function withVariantCode(string $code): self
    {
        $this->variantCode = $code;

        return $this;
    }

    public function withVariantName(string $name): self
    {
        $this->variantName = $name;

        return $this;
    }

    public function isShippingRequired(bool $isRequired): self
    {
        $this->isShippingRequired = $isRequired;

        return $this;
    }

    public function withQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function withUnitPrice(int $price): self
    {
        $this->unitPrice = $price;

        return $this;
    }

    public function build(): OrderItemInterface
    {
        $product = new Product();
        $product->setCurrentLocale('fr_FR');
        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setCurrentLocale('fr_FR');
        $variant->setCode($this->variantCode);
        $variant->setName($this->variantName);
        $variant->setShippingRequired($this->isShippingRequired);
        $orderItem = new OrderItem();
        $orderItem->setVariant($variant);
        $orderItem->setUnitPrice($this->unitPrice);
        for ($i = 0; $i < $this->quantity; ++$i) {
            $orderItem->addUnit(new OrderItemUnit($orderItem));
        }

        return $orderItem;
    }
}
