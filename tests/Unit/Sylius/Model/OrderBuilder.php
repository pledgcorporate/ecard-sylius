<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Prophecy\Prophet;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShipmentInterface;

class OrderBuilder
{
    /** @var int */
    private $id;

    /** @var int */
    private $total;

    private $number;

    /** @var string */
    private $localCode;

    /** @var AddressInterface */
    private $shippingAddress;

    /** @var AddressInterface */
    private $billingAddress;

    /** @var CustomerInterface */
    private $customer;

    /** @var ShipmentInterface[] */
    private $shipments = [];

    /** @var OrderItemInterface[] */
    private $items = [];

    public function __construct()
    {
        $prophet = new Prophet();
        $this->id = 1234;
        $this->total = 10000;
        $this->number = '123421234';
        $this->localCode = 'fr_FR';
        $this->shippingAddress = $prophet->prophesize(AddressInterface::class)->reveal();
        $this->billingAddress = $prophet->prophesize(AddressInterface::class)->reveal();
        $this->customer = $prophet->prophesize(CustomerInterface::class)->reveal();
    }

    public function withShippingAddress(AddressInterface $address): self
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function withBillingAddress(AddressInterface $address): self
    {
        $this->billingAddress = $address;

        return $this;
    }

    public function withCustomer(CustomerInterface $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @param array|ShipmentInterface[] $shipments
     *
     * @return $this
     */
    public function withShipments(array $shipments): self
    {
        $this->shipments = $shipments;

        return $this;
    }

    /**
     * @return $this
     */
    public function withItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function build(): OrderInterface
    {
        $order = new Order();
        $order->setBillingAddress($this->billingAddress);
        $order->setShippingAddress($this->shippingAddress);
        $order->setCustomer($this->customer);
        $order->setNumber($this->number);
        $order->setLocaleCode($this->localCode);
        foreach ($this->items as $item) {
            $order->addItem($item);
        }
        foreach ($this->shipments as $shipment) {
            $order->addShipment($shipment);
        }
        $reflectionClass = new \ReflectionClass(Order::class);
        $total = $reflectionClass->getProperty('total');
        $total->setAccessible(true);
        $total->setValue($order, $this->total);
        $id = $reflectionClass->getProperty('id');
        $id->setAccessible(true);
        $id->setValue($order, $this->id);

        return $order;
    }
}
