<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Sylius\Component\Core\Model\PaymentMethodInterface;

interface PaymentMethodProviderInterface
{
    /**
     * @return PaymentMethodInterface[]
     */
    public function getPledgMethods(): array;

    public function findOneByCode(string $code): PaymentMethodInterface;
}
