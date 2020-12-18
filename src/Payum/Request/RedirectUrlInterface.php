<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Request;


use Payum\Core\Request\Capture;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

interface RedirectUrlInterface
{
    public function getMerchant(): MerchantInterface;
    public static function fromCaptureAndMerchant(Capture $capture, MerchantInterface $merchant): self;
}
