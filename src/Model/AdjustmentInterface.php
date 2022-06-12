<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Model;

interface AdjustmentInterface extends \Sylius\Component\Core\Model\AdjustmentInterface
{
    public const PAYMENT_FEES_ADJUSTMENT = 'payment_fees';
}
