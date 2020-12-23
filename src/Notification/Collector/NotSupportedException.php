<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

class NotSupportedException extends \InvalidArgumentException implements CollectorException
{
    public static function fromContent(array $content): self
    {
        return new self(sprintf(
            'There is no notification collector that supports %s',
            json_encode($content, \JSON_THROW_ON_ERROR)
        ));
    }
}
