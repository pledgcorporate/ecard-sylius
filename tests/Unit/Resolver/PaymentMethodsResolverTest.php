<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Resolver\PaymentMethodsResolver;
use Prophecy\Argument;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\GatewayConfigBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentMethodBuilder;

class PaymentMethodsResolverTest extends TestCase
{
    /** @test */
    public function it_should_retrieve_other_supported_methods(): void
    {
        $paymentMethodsResolver = $this->createWithMethods([
            (new PaymentMethodBuilder())
                ->withConfig(
                    (new GatewayConfigBuilder())
                        ->withFactoryName('other')
                        ->build()
                )
                ->build(),
        ]);

        self::assertCount(1, $paymentMethodsResolver->getSupportedMethods(
            (new PaymentBuilder())->build()
        ));
    }

    private function createWithMethods(array $methods): PaymentMethodsResolverInterface
    {
        $paymentMethodsResolver = $this->prophesize(PaymentMethodsResolverInterface::class);
        $paymentMethodsResolver->getSupportedMethods(Argument::any())->willReturn($methods);

        return new PaymentMethodsResolver($paymentMethodsResolver->reveal());
    }
}
