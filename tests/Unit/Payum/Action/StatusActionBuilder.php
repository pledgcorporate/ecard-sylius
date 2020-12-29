<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;

use Pledg\SyliusPaymentPlugin\Payum\Action\StatusAction;
use Prophecy\Prophet;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusActionBuilder
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->requestStack = $prophet->prophesize(RequestStack::class)->reveal();
    }

    public function withRequestStack(RequestStack $requestStack): self
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    public function build(): StatusAction
    {
        return new StatusAction($this->requestStack);
    }
}
