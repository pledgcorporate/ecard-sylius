<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Prophecy\Prophet;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Model\AdjustmentInterface;

class OrderBuilder
{
    /** @var int */
    private $id;

    private $number;

    /** @var string */
    private $localCode;

    /** @var string */
    private $currencyCode;

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

    /** @var PaymentInterface[] */
    private $payments = [];

    /** @var AdjustmentInterface[] */
    private $adjustments = [];

    public function __construct()
    {
        $prophet = new Prophet();
        $this->id = 1234;
        $this->number = '123421234';
        $this->localCode = 'fr_FR';
        $this->currencyCode = 'EUR';
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
     * @param array|PaymentInterface[] $payments
     *
     * @return $this
     */
    public function withPayments(array $payments): self
    {
        $this->payments = $payments;

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

    public function withAdjustments(array $adjustments): self
    {
        $this->adjustments = $adjustments;

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
        $order->setCurrencyCode($this->currencyCode);
        foreach ($this->items as $item) {
            $order->addItem($item);
        }
        foreach ($this->shipments as $shipment) {
            $order->addShipment($shipment);
        }
        foreach ($this->payments as $payment) {
            $order->addPayment($payment);
        }
        foreach ($this->adjustments as $adjustment) {
            $order->addAdjustment($adjustment);
        }
        $order->recalculateItemsTotal();
        $order->recalculateAdjustmentsTotal();
        $reflectionClass = new \ReflectionClass(Order::class);
        $id = $reflectionClass->getProperty('id');
        $id->setAccessible(true);
        $id->setValue($order, $this->id);

        return $order;
    }
}
