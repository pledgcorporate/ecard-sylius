<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Resolver\PaymentMethodsResolver;
use Prophecy\Argument;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\AddressBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\GatewayConfigBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderBuilder;
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

        self::assertCount(
            1,
            $paymentMethodsResolver->getSupportedMethods($this->createPaymentWithBillingCountry('FR'))
        );
    }

    /** @test */
    public function it_should_retrieve_pledg_payment_method_with_restricted_country(): void
    {
        $expectedMethodName = 'pledg_fr';
        $paymentMethodsResolver = $this->createWithMethods([
            (new PaymentMethodBuilder())
                ->withName($expectedMethodName)
                ->withConfig(
                    (new GatewayConfigBuilder())
                        ->withFactoryName('pledg')
                        ->withConfig('restricted_countries', ['FR'])
                        ->build()
                )
                ->build(),
            (new PaymentMethodBuilder())
                ->withName('pledg_en')
                ->withConfig(
                    (new GatewayConfigBuilder())
                        ->withFactoryName('pledg')
                        ->withConfig('restricted_countries', ['EN'])
                        ->build()
                )
                ->build(),
        ]);

        $methods = $paymentMethodsResolver->getSupportedMethods($this->createPaymentWithBillingCountry('FR'));

        self::assertCount(1, $methods);
        self::assertSame($expectedMethodName, $methods[0]->getName());
    }

    private function createWithMethods(array $methods): PaymentMethodsResolverInterface
    {
        $paymentMethodsResolver = $this->prophesize(PaymentMethodsResolverInterface::class);
        $paymentMethodsResolver->getSupportedMethods(Argument::any())->willReturn($methods);

        return new PaymentMethodsResolver($paymentMethodsResolver->reveal());
    }

    private function createPaymentWithBillingCountry(string $country): PaymentInterface
    {
        return (new PaymentBuilder())
            ->withOrder(
                (new OrderBuilder())
                    ->withBillingAddress(
                        (new AddressBuilder())
                            ->withCountry($country)
                            ->build()
                    )
                    ->build()
            )
            ->build();
    }
}
