<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

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
        $this->method = (new PaymentMethodBuilder())->build();
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
        $this->method = (new PaymentMethodBuilder())
            ->withConfig(
                (new GatewayConfigBuilder())
                    ->withConfig('identifier', $merchant->getIdentifier())
                    ->withConfig('secret', $merchant->getSecret())
                    ->build()
            )
            ->build();

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
