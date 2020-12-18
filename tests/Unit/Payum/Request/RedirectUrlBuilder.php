<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;


use Payum\Core\Request\Capture;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\AddressBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\CustomerBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class RedirectUrlBuilder extends RequestBuilder
{
    /** @var Merchant */
    protected $merchant;

    /** @var Capture */
    protected $capture;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = (new MerchantBuilder())->build();
        $this->capture = (new CaptureBuilder())->build();
    }

    public function withCapture(Capture $capture): self
    {
        $this->capture = $capture;

        return $this;
    }

    public function withCompleteCaptureRequest(): self
    {
        return $this->withCapture((new CaptureBuilder())
            ->withPayment(
                (new PaymentBuilder())
                    ->withOrder(
                        (new OrderBuilder())
                            ->withBillingAddress((new AddressBuilder())->build())
                            ->withShippingAddress((new AddressBuilder())->build())
                            ->withCustomer((new CustomerBuilder())->build())
                            ->build()
                    )
                    ->build()
            )
            ->build()
        );
    }

    public function build(): RedirectUrlInterface
    {
       return RedirectUrl::fromCaptureAndMerchant($this->capture, $this->merchant);
    }
}
