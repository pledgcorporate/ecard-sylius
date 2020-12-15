<?php

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Factory;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;

class PledgGatewayFactoryTest extends TestCase
{
    /** @test */
    public function it_populates_pledg_factory_configuration(): void
    {
        $factory = new PledgGatewayFactory();

        $config = $factory->createConfig();

        self::assertEquals('pledg', $config['payum.factory_name']);
        self::assertEquals('Pledg', $config['payum.factory_title']);

    }
}
