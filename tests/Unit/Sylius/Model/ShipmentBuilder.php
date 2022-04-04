<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Core\Model\ShippingMethodInterface;

class ShipmentBuilder
{
    /** @var ShippingMethodInterface */
    protected $shippingMethod;

    public function __construct()
    {
        $this->shippingMethod = new ShippingMethod();
        $this->shippingMethod->setCurrentLocale('fr_FR');
    }

    public function withSippingMethodName(string $name): self
    {
        $this->shippingMethod->setName($name);

        return $this;
    }

    public function build(): ShipmentInterface
    {
        $shipment = new Shipment();
        $shipment->setMethod($this->shippingMethod);

        return $shipment;
    }
}
