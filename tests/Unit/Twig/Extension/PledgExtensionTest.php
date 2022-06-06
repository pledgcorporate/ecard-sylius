<?php

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Twig\Extension\PledgExtension;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\GatewayConfigBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentMethodBuilder;

class PledgExtensionTest extends TestCase
{
    /** @test */
    public function it_should_return_true_if_method_is_pledg(): void
    {
        $methodCode = 'pledg_code';

        $extension = $this->createExtensionWithMethods([
            (new PaymentMethodBuilder())
                ->withCode($methodCode)
                ->withConfig(
                    (new GatewayConfigBuilder())
                        ->withFactoryName(PledgGatewayFactory::NAME)
                        ->build()
                )
                ->build()
        ]);

        self::assertTrue($extension->isPledgMethod($methodCode));
    }

    /** @test */
    public function it_should_return_false_if_method_is_not_pledg(): void
    {
        $methodCode = 'other_method';

        $extension = $this->createExtensionWithMethods([
            (new PaymentMethodBuilder())
                ->withCode('pledg_code')
                ->withConfig(
                    (new GatewayConfigBuilder())
                        ->withFactoryName(PledgGatewayFactory::NAME)
                        ->build()
                )
                ->build()
        ]);

        self::assertFalse($extension->isPledgMethod($methodCode));
    }

    private function createExtensionWithMethods(array $methods): PledgExtension
    {
        $repository = $this->prophesize(PaymentMethodProviderInterface::class);
        $repository->getPledgMethods()->willReturn($methods);

        return new PledgExtension($repository->reveal());
    }
}