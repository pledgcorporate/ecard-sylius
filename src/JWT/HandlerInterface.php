<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\JWT;

interface HandlerInterface
{
    public function encode(array $parameters, string $secret): string;

    public function decode(string $token): array;

    public function verify(string $token, string $secret): bool;
}
