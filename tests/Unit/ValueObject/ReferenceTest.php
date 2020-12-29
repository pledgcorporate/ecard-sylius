<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;

class ReferenceTest extends TestCase
{
    /** @test */
    public function it_returns_valid_pledg_reference(): void
    {
        $reference = new Reference(1234, 1235);

        self::assertSame(1234, $reference->getOrderId());
        self::assertSame(1235, $reference->getPaymentId());
        self::assertSame('PLEDG_1234_1235', (string) $reference);
    }

    /** @test */
    public function it_can_be_initialized_from_string(): void
    {
        $reference = Reference::fromString('PLEDG_1234_1234');

        self::assertSame(1234, $reference->getOrderId());
        self::assertSame(1234, $reference->getPaymentId());
    }

    /** @test */
    public function it_can_not_be_initialized_from_string_with_invalid_identifiers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('The reference is invalid : PLEDG_0001234_00123 provide PLEDG_1234_123 expected');
        Reference::fromString('PLEDG_0001234_00123');
    }

    /** @test */
    public function it_can_not_be_initialized_from_string_with_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('The reference PLEDG_1234 is invalid the format should be PLEG_ORDERID_PAYMENTID');
        Reference::fromString('PLEDG_1234');
    }

    public function it_can_not_be_initialized_from_string_with_invalid_integers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('The reference is invalid : PLEDG_invalid_00123 provide PLEDG_1234_123 expected');
        Reference::fromString('PLEDG_invalid_00123');
    }
}
