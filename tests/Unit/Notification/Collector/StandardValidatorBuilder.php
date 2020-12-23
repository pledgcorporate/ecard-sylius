<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;


use Pledg\SyliusPaymentPlugin\Notification\Collector\StandardValidator;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProvider;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;

class StandardValidatorBuilder
{
    /** @var MerchantProviderInterface */
    protected $merchantProvider;

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    public function __construct()
    {
        $this->merchantProvider = new MerchantProvider();
        $this->paymentProvider = (new PaymentProviderBuilder())->build();
    }

    public function withPaymentProvider(PaymentProviderInterface $paymentProvider): self
    {
        $this->paymentProvider = $paymentProvider;

        return $this;
    }

    public function build(): ValidatorInterface
    {
        return new StandardValidator($this->paymentProvider, $this->merchantProvider);
    }
}
