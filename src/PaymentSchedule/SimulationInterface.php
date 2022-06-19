<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\PaymentSchedule;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

interface SimulationInterface
{
    public function simulate(MerchantInterface $merchant, int $amount, \DateTimeInterface $createdAt): PaymentSchedule;
}
