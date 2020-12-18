<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Request;

use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

class RedirectUrl extends Generic implements RedirectUrlInterface
{
    /** @var MerchantInterface */
    private $merchant;

    public function getMerchant(): MerchantInterface
    {
        return $this->merchant;
    }

    public static function fromCaptureAndMerchant(Capture $capture, MerchantInterface $merchant): RedirectUrlInterface
    {
        $request = new self($capture->getToken());
        $request->setFirstModel($capture->getFirstModel());
        $request->setModel($capture->getModel());
        $request->merchant = $merchant;

        return $request;
    }
}
