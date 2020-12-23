<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Symfony\Component\Routing\RouterInterface;

class ParamBuilderFactory implements ParamBuilderFactoryInterface
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface
    {
        return ParamBuilder::fromRedirectUrlRequest($request, $this->router);
    }
}
