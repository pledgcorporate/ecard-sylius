<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;

use Pledg\SyliusPaymentPlugin\Payum\Action\StatusAction;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusActionBuilder
{
    /** @var RequestStack */
    private $requestStack;

    /** @var LoggerInterface */
    private $logger;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->requestStack = $prophet->prophesize(RequestStack::class)->reveal();
        $this->logger = new NullLogger();
    }

    public function withRequestStack(RequestStack $requestStack): self
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    public function build(): StatusAction
    {
        return new StatusAction($this->requestStack, $this->logger);
    }
}
