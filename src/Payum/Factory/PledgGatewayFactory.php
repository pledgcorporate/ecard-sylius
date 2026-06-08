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

    public const API_KEY = 'api_key';

    public const API_SECRET = 'api_secret';

    public const COMPANY_UID = 'company_uid';

    public const WIDGET_PRODUCT_ENABLED = 'widget_product_enabled';

    public const WIDGET_CHECKOUT_ENABLED = 'widget_checkout_enabled';

    public const WIDGET_CATALOG_ENABLED = 'widget_catalog_enabled';

    public const SANDBOX = 'sandbox';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => self::NAME,
            'payum.factory_title' => 'PledgBySofinco',
            'payum.api' => function (ArrayObject $config): Merchant {
                /** @var string $identifier */
                $identifier = $config[self::IDENTIFIER];
                /** @var string $secret */
                $secret = $config[self::SECRET];

                return new Merchant($identifier, $secret);
            },
        ]);
    }
}
