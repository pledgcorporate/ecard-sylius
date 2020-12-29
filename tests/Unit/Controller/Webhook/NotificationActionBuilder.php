<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Controller\Webhook;

use Doctrine\Persistence\ObjectManager;
use Pledg\SyliusPaymentPlugin\Controller\Webhook\NotificationAction;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector\StandardProcessorBuilder;

class NotificationActionBuilder
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var ObjectManager */
    private $paymentManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct()
    {
        $this->processor = (new StandardProcessorBuilder())->build();
        $this->logger = new NullLogger();
        $prophet = new Prophet();
        $this->paymentManager = $prophet->prophesize(ObjectManager::class)->reveal();
    }

    public function build(): NotificationAction
    {
        return new NotificationAction($this->processor, $this->paymentManager, $this->logger);
    }
}
