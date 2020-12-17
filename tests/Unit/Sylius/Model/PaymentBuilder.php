<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;


use Prophecy\Prophet;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

class PaymentBuilder
{
    /** @var PaymentMethodInterface */
    private $method;

    /** @var OrderInterface */
    private $order;

    /** @var string */
    private $currencyCode;

    /** @var integer */
    private $amount;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->method = $prophet->prophesize(PaymentMethodInterface::class)->reveal();
        $this->order = (new OrderBuilder())->build();
        $this->currencyCode = 'EUR';
        $this->amount = 10000;
    }

    public function build(): PaymentInterface
    {
        $payment = new Payment();
        $payment->setMethod($this->method);
        $payment->setOrder($this->order);
        $payment->setAmount($this->amount);
        $payment->setCurrencyCode($this->currencyCode);

        return $payment;
    }
}
