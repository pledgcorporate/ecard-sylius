<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO;

class Payment
{
    /** @var \DateTimeInterface */
    public $date;

    /** @var int */
    public $amount;

    /** @var int */
    public $fees;
}
