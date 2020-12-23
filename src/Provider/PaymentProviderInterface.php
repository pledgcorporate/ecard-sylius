<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Sylius\Component\Core\Model\PaymentInterface;

interface PaymentProviderInterface
{
    public function getByReference(string $reference): PaymentInterface;
}
