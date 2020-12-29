<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProvider;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class MerchantProviderTest extends TestCase
{
    /** @test */
    public function it_finds_merchant_by_payment(): void
    {
        $merchant = (new MerchantBuilder())->build();
        $payment = (new PaymentBuilder())->withMerchant($merchant)->build();

        $result = (new MerchantProvider())->findByPayment($payment);

        self::assertSame($merchant->getSecret(), $result->getSecret());
    }
}
