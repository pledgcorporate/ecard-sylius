<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

interface ValidatorInterface
{
    public function supports(array $content): bool;

    public function validate(array $content): bool;
}
