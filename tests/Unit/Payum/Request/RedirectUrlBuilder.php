<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;


use Payum\Core\Request\Capture;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class RedirectUrlBuilder extends RequestBuilder
{
    /** @var Merchant */
    protected $merchant;

    /** @var Capture */
    protected $capture;

    public function __construct()
    {
        $this->merchant = (new MerchantBuilder())->build();
        $this->capture = (new CaptureBuilder())->build();

        parent::__construct();
    }

    public function build(): RedirectUrl
    {
       return RedirectUrl::fromCaptureAndMerchant($this->capture, $this->merchant);
    }
}
