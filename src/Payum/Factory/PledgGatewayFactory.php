<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;

class PledgGatewayFactory extends GatewayFactory
{
    public const NAME = 'pledg';

    public const RESTRICTED_COUNTRIES = 'restricted_countries';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => self::NAME,
            'payum.factory_title' => 'Pledg',
            'payum.api' => function (ArrayObject $config): Merchant {
                return new Merchant($config['identifier'], $config['secret']);
            },
        ]);
    }
}
