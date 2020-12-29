<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;

use Payum\Core\Request\Capture;
use Sylius\Component\Core\Model\PaymentInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;

class CaptureBuilder extends RequestBuilder
{
    /** @var PaymentInterface */
    protected $payment;

    public function __construct()
    {
        $this->payment = (new PaymentBuilder())->build();

        parent::__construct();
    }

    public function withPayment(PaymentInterface $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function build(): Capture
    {
        $request = new Capture($this->token);
        $request->setModel($this->payment);

        return $request;
    }
}
