<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;


use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;

class MerchantTest extends TestCase
{
    /** @test */
    public function it_initialize_valid_merchant(): void
    {
        $merchant = (new MerchantBuilder())->build();

        self::assertSame(MerchantBuilder::VALID_IDENTIFIER, $merchant->getIdentifier());
        self::assertSame(MerchantBuilder::VALID_SECRET, $merchant->getSecret());
    }
}
