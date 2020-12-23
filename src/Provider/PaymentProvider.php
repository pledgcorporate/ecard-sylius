<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;

final class PaymentProvider implements PaymentProviderInterface
{
    /** @var PaymentRepositoryInterface */
    private $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function getByReference(string $reference): PaymentInterface
    {
        $vo = Reference::fromString($reference);

        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->find($vo->getPaymentId());

        return $payment;
    }
}
