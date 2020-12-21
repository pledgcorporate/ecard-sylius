<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Request;

use Payum\Core\Model\ModelAggregateInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenAggregateInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Sylius\Component\Core\Model\PaymentInterface;

interface RedirectUrlInterface extends ModelAggregateInterface, TokenAggregateInterface
{
    public function getMerchant(): MerchantInterface;

    public function getPayment(): PaymentInterface;

    public static function fromCaptureAndMerchant(Capture $capture, MerchantInterface $merchant): self;
}
