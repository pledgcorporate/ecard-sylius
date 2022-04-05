<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;

class PledgGatewayFactoryTest extends TestCase
{
    /** @test */
    public function it_populates_pledg_configuration(): void
    {
        $factory = new PledgGatewayFactory();

        $config = $factory->createConfig();

        self::assertSame('pledg', $config['payum.factory_name']);
        self::assertSame('Pledg', $config['payum.factory_title']);
    }

    /** @test */
    public function it_populates_pledg_configuration_with_merchant(): void
    {
        $factory = new PledgGatewayFactory();

        $config = $factory->createConfig();

        self::assertInstanceOf(\Closure::class, $config['payum.api']);
        self::assertInstanceOf(Merchant::class, $config['payum.api'](new ArrayObject([
            PledgGatewayFactory::IDENTIFIER => 'mer_aee4846c-ac62-4835-8adf-bea9f8737144',
            PledgGatewayFactory::SECRET => 'aIDZLuoAdK8NAqoFIFPBao72WEQ6jrWMvYwaXaiO',
        ])));
    }
}
