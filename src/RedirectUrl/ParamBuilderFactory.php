<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;


use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;

class ParamBuilderFactory implements ParamBuilderFactoryInterface
{
    public function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface
    {
        return ParamBuilder::fromRedirectUrlRequest($request);
    }
}
