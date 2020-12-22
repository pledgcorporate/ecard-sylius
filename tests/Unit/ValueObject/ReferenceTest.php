<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;


use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;

class ReferenceTest extends TestCase
{
    /** @test */
    public function it_returns_valid_pledg_reference(): void
    {
        $reference = new Reference(1234);

        self::assertSame('PLEDG_1234', (string) $reference);
    }

    /** @test */
    public function it_can_be_initialized_from_string(): void
    {
        $reference = Reference::fromString('PLEDG_1234');

        self::assertSame(1234, $reference->getId());
    }

    /** @test */
    public function it_can_not_be_initialized_from_string_with_invalid_reference(): void
    {
       $this->expectException(\InvalidArgumentException::class);

        Reference::fromString('PLEDG_0001234');
    }
}
