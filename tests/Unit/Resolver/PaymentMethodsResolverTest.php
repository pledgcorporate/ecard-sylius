<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Resolver\PaymentMethodsResolver;
use Prophecy\Argument;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
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
            $paymentMethodsResolver->getSupportedMethods($this->createPaymentWith('FR'))
        );
    }

    /** @test */
    public function it_should_retrieve_method_with_restricted_country(): void
    {
        $expectedMethodName = 'pledg_fr';
        $paymentMethodsResolver = $this->createWithMethods([
            $this->createMethodWith($expectedMethodName, ['FR']),
            $this->createMethodWith($expectedMethodName, ['EN']),
        ]);

        $methods = $paymentMethodsResolver->getSupportedMethods($this->createPaymentWith('FR'));

        self::assertCount(1, $methods);
        self::assertSame($expectedMethodName, $methods[0]->getName());
    }

    /** @test */
    public function it_should_retrieve_methods_restricted_by_minimum_price(): void
    {
        $expectedMethodName = 'pledg_fr_min_1';
        $paymentMethodsResolver = $this->createWithMethods([
            $this->createMethodWith($expectedMethodName, ['FR'], ['min' => 1]),
            $this->createMethodWith('other_method', ['FR'], ['min' => 101]),
        ]);

        $methods = $paymentMethodsResolver->getSupportedMethods($this->createPaymentWith('FR', 100));

        self::assertCount(1, $methods);
        self::assertSame($expectedMethodName, $methods[0]->getName());
    }

    /** @test */
    public function it_should_retrieve_methods_restricted_by_maximum_price(): void
    {
        $expectedMethodName = 'pledg_fr_max_100';
        $paymentMethodsResolver = $this->createWithMethods([
            $this->createMethodWith($expectedMethodName, ['FR'], ['max' => 100]),
            $this->createMethodWith('other_method', ['FR'], ['max' => 99]),
        ]);

        $methods = $paymentMethodsResolver->getSupportedMethods($this->createPaymentWith('FR', 100));

        self::assertCount(1, $methods);
        self::assertSame($expectedMethodName, $methods[0]->getName());
    }

    private function createWithMethods(array $methods): PaymentMethodsResolverInterface
    {
        $paymentMethodsResolver = $this->prophesize(PaymentMethodsResolverInterface::class);
        $paymentMethodsResolver->getSupportedMethods(Argument::any())->willReturn($methods);

        return new PaymentMethodsResolver($paymentMethodsResolver->reveal());
    }

    private function createPaymentWith(string $billingCountryCode, int $amount = null): PaymentInterface
    {
        return (new PaymentBuilder())
            ->withAmountInCents($amount ? ($amount * 100) : 10000)
            ->withOrder(
                (new OrderBuilder())
                    ->withBillingAddress(
                        (new AddressBuilder())
                            ->withCountry($billingCountryCode)
                            ->build()
                    )
                    ->build()
            )
            ->build();
    }

    private function createMethodWith(string $name, array $restrictedCountries, array $range = []): PaymentMethodInterface
    {
        return (new PaymentMethodBuilder())
            ->withName($name)
            ->withConfig(
                (new GatewayConfigBuilder())
                    ->withFactoryName('pledg')
                    ->withConfig(PledgGatewayFactory::RESTRICTED_COUNTRIES, $restrictedCountries)
                    ->withConfig(PledgGatewayFactory::PRICE_RANGE_MIN, $range['min'] ?? null)
                    ->withConfig(PledgGatewayFactory::PRICE_RANGE_MAX, $range['max'] ?? null)
                    ->build()
            )
            ->build();
    }
}
