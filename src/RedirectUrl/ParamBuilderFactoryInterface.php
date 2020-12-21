<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;

interface ParamBuilderFactoryInterface
{
    public function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface;
}
