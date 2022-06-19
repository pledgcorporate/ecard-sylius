<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Sylius\Component\Order\Model\Adjustment;
use Sylius\Component\Order\Model\AdjustmentInterface;

class AdjustmentBuilder
{
    /** @var int */
    protected $amount;

    /** @var bool */
    protected $neutral;

    /** @var string */
    protected $type;

    public function withNotNeutralPledgFees(int $fees): self
    {
        $this->amount = $fees;
        $this->type = \Pledg\SyliusPaymentPlugin\Model\AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT;
        $this->neutral = false;

        return $this;
    }

    public function build(): AdjustmentInterface
    {
        $adjustment = new Adjustment();
        $adjustment->setAmount($this->amount);
        $adjustment->setType($this->type);
        $adjustment->setNeutral($this->neutral);

        return $adjustment;
    }
}
