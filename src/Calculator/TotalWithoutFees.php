<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Calculator;

use Pledg\SyliusPaymentPlugin\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;

class TotalWithoutFees implements TotalWithoutFeesInterface
{
    public function calculate(OrderInterface $order): int
    {
        $feesAdjustments = $order->getAdjustments(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);

        if ($feesAdjustments->isEmpty()) {
            return $order->getTotal();
        }

        $fees = $feesAdjustments->map(static function (\Sylius\Component\Order\Model\AdjustmentInterface $adjustment): int {
            return $adjustment->getAmount();
        });

        return $order->getTotal() - array_sum($fees->toArray());
    }
}
