<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Pledg\SyliusPaymentPlugin\Calculator\TotalWithoutFeesInterface;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Symfony\Component\Routing\RouterInterface;

final class ParamBuilderFactory implements ParamBuilderFactoryInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var TotalWithoutFeesInterface */
    private $totalWithoutFees;

    public function __construct(TotalWithoutFeesInterface $totalWithoutFees, RouterInterface $router)
    {
        $this->totalWithoutFees = $totalWithoutFees;
        $this->router = $router;
    }

    public function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface
    {
        return ParamBuilder::fromRedirectUrlRequest($request, $this->totalWithoutFees, $this->router);
    }
}
