<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PledgGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'pledg',
            'payum.factory_title' => 'Pledg',
        ]);
    }
}
