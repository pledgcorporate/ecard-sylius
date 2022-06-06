<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Sylius\Component\Payment\Model\PaymentMethodInterface;

interface PaymentMethodProviderInterface
{
    /**
     * @return PaymentMethodInterface[]
     */
    public function getPledgMethods(): array;
}
