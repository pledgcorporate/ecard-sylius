<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

class InvalidSignatureException extends \InvalidArgumentException implements CollectorException
{
    public static function withSignatureAndContent(string $signature, array $content): self
    {
        return new self(sprintf(
            'The signature %s is not valid with the content %s',
            $signature,
            json_encode($content, \JSON_THROW_ON_ERROR)
        ));
    }

    public static function withSignature(string $signature): self
    {
        return new self(sprintf('The signature %s is not valid', $signature));
    }
}
