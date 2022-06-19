<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

interface MerchantProviderInterface
{
    public function findByPayment(PaymentInterface $payment): MerchantInterface;

    public function findByMethod(PaymentMethodInterface $method): MerchantInterface;
}
