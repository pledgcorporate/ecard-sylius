<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Sylius\Component\Core\Model\PaymentInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;

class PaymentProviderTest extends TestCase
{
    /** @test */
    public function it_finds_payment_by_reference(): void
    {
        $reference = new Reference('1234', 1234);

        $paymentRepository = new InMemoryPaymentRepository();
        $payment = (new PaymentBuilder())->withId(1234)->build();
        $paymentRepository->add($payment);
        $paymentProvider = (new PaymentProviderBuilder())->withRepository($paymentRepository)->build();

        $payment = $paymentProvider->getByReference((string) $reference);

        self::assertInstanceOf(PaymentInterface::class, $payment);
    }
}
