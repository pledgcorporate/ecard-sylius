<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\PaymentSchedule;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

interface SimulationInterface
{
    /**
     * @param string|null $scheduleMerchantUid Optional UID (e.g. company) for the simulation API path
     * @param string|null $backUrlOverride     Base URL for the Pledg back office API
     */
    public function simulate(MerchantInterface $merchant, int $amount, \DateTimeInterface $createdAt, ?string $scheduleMerchantUid = null, ?string $backUrlOverride = null): PaymentSchedule;

    /**
     * @return array<int, array{nb: int, caption: string, taeg_str: string, has_fees: bool, schedule: array}>
     */
    public function simulateForWidget(int $amountCents, string $uid, string $baseUrl): array;
}
