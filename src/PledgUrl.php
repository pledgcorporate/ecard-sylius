<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin;

final class PledgUrl
{
    public const SANDBOX_FRONT = 'https://staging.front.ecard.pledg.co';

    public const PROD_FRONT = 'https://front.ecard.pledg.co';

    public const SANDBOX_BACK = 'https://staging.back.ecard.pledg.co';

    public const PROD_BACK = 'https://back.ecard.pledg.co';

    public static function frontUrl(bool $sandbox): string
    {
        return $sandbox ? self::SANDBOX_FRONT : self::PROD_FRONT;
    }

    public static function backUrl(bool $sandbox): string
    {
        return $sandbox ? self::SANDBOX_BACK : self::PROD_BACK;
    }
}
