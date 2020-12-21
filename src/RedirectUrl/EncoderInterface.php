<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

interface EncoderInterface
{
    public function encode(array $parameters, string $secret): string;
}
