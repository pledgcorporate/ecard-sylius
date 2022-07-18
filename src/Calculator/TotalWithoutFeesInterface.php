<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Calculator;

use Sylius\Component\Core\Model\OrderInterface;

interface TotalWithoutFeesInterface
{
    public function calculate(OrderInterface $order): int;
}
