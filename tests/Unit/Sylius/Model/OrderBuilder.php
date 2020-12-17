<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;


use Prophecy\Prophet;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;

class OrderBuilder
{
    /** @var int */
    private $total;

    /** @var AddressInterface */
    private $shippingAddress;

    /** @var AddressInterface */
    private $billingAddress;

    /** @var CustomerInterface */
    private $customer;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->total = 10000;
        $this->shippingAddress = $prophet->prophesize(AddressInterface::class)->reveal();
        $this->billingAddress = $prophet->prophesize(AddressInterface::class)->reveal();
        $this->customer = $prophet->prophesize(CustomerInterface::class)->reveal();
    }

    public function build(): OrderInterface
    {
        $order = new Order();
        $order->setBillingAddress($this->billingAddress);
        $order->setShippingAddress($this->shippingAddress);
        $order->setCustomer($this->customer);
        $reflectionClass = new \ReflectionClass(Order::class);
        $total =  $reflectionClass->getProperty('total');
        $total->setAccessible(true);
        $total->setValue($order, $this->total);

        return $order;
    }
}
