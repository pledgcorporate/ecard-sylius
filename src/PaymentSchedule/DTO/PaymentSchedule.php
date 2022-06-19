<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO;

class PaymentSchedule
{
    /** @var Payment[] */
    public $payments = [];

    public function getFees(): int
    {
        return array_reduce($this->payments, function (int $total, Payment $payment): int {
            return $total + $payment->fees;
        }, 0);
    }

    public static function fromArray(array $paymentSchedule): self
    {
        $dto = new self();

        foreach ($paymentSchedule as $payment) {
            $paymentDto = new Payment();
            $paymentDto->date = new \DateTimeImmutable($payment['payment_date']);
            $paymentDto->amount = (int) $payment['amount_cents'];
            $paymentDto->fees = (int) $payment['fees'];
            $dto->payments[] = $paymentDto;
        }

        return $dto;
    }
}
