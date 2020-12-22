<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Sylius\Component\Core\Model\PaymentInterface;

interface MerchantProviderInterface
{
    public function findByPayment(PaymentInterface $payment): MerchantInterface;
}
