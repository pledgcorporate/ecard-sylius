<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

interface ParamBuilderInterface
{
    public function build(): array;
}
