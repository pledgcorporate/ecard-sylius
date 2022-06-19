<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Processor;

use Sylius\Component\Order\Model\OrderInterface;

interface PaymentFeeProcessorInterface
{
    public function process(OrderInterface $order): void;
}
