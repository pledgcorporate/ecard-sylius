<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;
use Pledg\SyliusPaymentPlugin\Notification\Collector\TransferValidator;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProvider;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;

class TransferValidatorBuilder
{
    /** @var MerchantProviderInterface */
    protected $merchantProvider;

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var HandlerInterface */
    protected $handler;

    public function __construct()
    {
        $this->merchantProvider = new MerchantProvider();
        $this->paymentProvider = (new PaymentProviderBuilder())->build();
        $this->handler = new HS256Handler();
    }

    public function withPaymentProvider(PaymentProviderInterface $paymentProvider): self
    {
        $this->paymentProvider = $paymentProvider;

        return $this;
    }

    public function build(): ValidatorInterface
    {
        return new TransferValidator($this->paymentProvider, $this->merchantProvider, $this->handler);
    }
}
