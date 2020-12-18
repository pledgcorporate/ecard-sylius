<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;


use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;

interface ParamBuilderFactoryInterface
{
    public function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface;
}
