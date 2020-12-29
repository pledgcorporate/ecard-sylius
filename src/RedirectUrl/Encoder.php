<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;

/**
 * Encode parameters with JWT HS256 algorithm
 */
class Encoder implements EncoderInterface
{
    /** @var HandlerInterface */
    protected $handler;

    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function encode(array $parameters, string $secret): string
    {
        return $this->handler->encode(['data' => $parameters], $secret);
    }
}
