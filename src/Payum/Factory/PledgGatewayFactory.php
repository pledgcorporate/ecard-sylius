<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;

class PledgGatewayFactory extends GatewayFactory
{
    public const NAME = 'pledgbysofinco';

    public const IDENTIFIER = 'identifier';

    public const SECRET = 'secret';

    public const RESTRICTED_COUNTRIES = 'restricted_countries';

    public const PRICE_RANGE_MIN = 'price_range_min';

    public const PRICE_RANGE_MAX = 'price_range_max';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => self::NAME,
            'payum.factory_title' => 'PledgBySofinco',
            'payum.api' => function (ArrayObject $config): Merchant {
                return new Merchant($config[self::IDENTIFIER], $config[self::SECRET]);
            },
        ]);
    }
}
