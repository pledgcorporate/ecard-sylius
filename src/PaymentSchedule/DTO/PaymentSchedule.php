<?php

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO;

class PaymentSchedule
{
    /** @var Payment[] */
    public $payments = [];

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