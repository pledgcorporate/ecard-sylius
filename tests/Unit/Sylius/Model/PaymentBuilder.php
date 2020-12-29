<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Payum\Core\Model\GatewayConfigInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Prophecy\Prophet;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

class PaymentBuilder
{
    /** @var int */
    private $id;

    /** @var PaymentMethodInterface */
    private $method;

    /** @var OrderInterface */
    private $order;

    /** @var string */
    private $currencyCode;

    /** @var int */
    private $amount;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->method = $prophet->prophesize(PaymentMethodInterface::class)->reveal();
        $this->order = (new OrderBuilder())->build();
        $this->currencyCode = 'EUR';
        $this->amount = 10000;
        $this->id = 1234;
    }

    public function withId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withOrder(OrderInterface $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function withMerchant(Merchant $merchant): self
    {
        $prophet = new Prophet();
        $method = $prophet->prophesize(PaymentMethodInterface::class);
        $gatewayConfig = $prophet->prophesize(GatewayConfigInterface::class);
        $gatewayConfig->getConfig()->willReturn([
            'identifier' => $merchant->getIdentifier(),
            'secret' => $merchant->getSecret(),
        ]);
        $method->getGateWayConfig()->willReturn($gatewayConfig->reveal());

        $this->method = $method->reveal();

        return $this;
    }

    public function build(): PaymentInterface
    {
        $payment = new Payment();
        $payment->setMethod($this->method);
        $payment->setOrder($this->order);
        $payment->setAmount($this->amount);
        $payment->setCurrencyCode($this->currencyCode);
        $reflectionClass = new \ReflectionClass(Payment::class);
        $id = $reflectionClass->getProperty('id');
        $id->setAccessible(true);
        $id->setValue($payment, $this->id);

        return $payment;
    }
}
