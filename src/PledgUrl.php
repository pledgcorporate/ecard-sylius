<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin;

interface PledgUrl
{
    public const SANDBOX_FRONT = 'https://staging.front.ecard.pledg.co';

    public const PROD_FRONT = 'https://front.ecard.pledg.co';

    public const SANDBOX_BACK = 'https://staging.back.ecard.pledg.co';

    public const PROD_BACK = 'https://back.ecard.pledg.co';
}
