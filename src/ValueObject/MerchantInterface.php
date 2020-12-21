<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\ValueObject;

interface MerchantInterface
{
    public function getIdentifier(): string;

    public function getSecret(): string;
}
