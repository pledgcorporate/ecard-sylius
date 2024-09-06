<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\JWT;

class HS256Handler implements HandlerInterface
{
    public function encode(array $parameters, string $secret): string
    {
        $segments = [
            $this->safeBase65Encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], \JSON_THROW_ON_ERROR)), //build the headers,
            $this->safeBase65Encode(json_encode($parameters, \JSON_THROW_ON_ERROR)), // build Parameters
        ];

        $segments[] = $this->safeBase65Encode(hash_hmac('sha256', implode('.', $segments), $secret, true)); // build the signature

        return implode('.', $segments);
    }

    public function verify(string $token, string $secret): bool
    {
        [$headerEncoded, $bodyEncoded, $signatureEncoded] = explode('.', $token);
        $signature = $this->safeBase64Decode($signatureEncoded);

        $hash = hash_hmac('sha256', implode('.', [$headerEncoded, $bodyEncoded]), $secret, true);

        return \hash_equals($signature, $hash);
    }

    public function decode(string $token): array
    {
        $encodedParameters = explode('.', $token)[1];

        /** @var array $ret */
        $ret = json_decode($this->safeBase64Decode($encodedParameters), true, 512, \JSON_THROW_ON_ERROR);
        return $ret;
    }

    private function safeBase65Encode(string $input): string
    {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }

    private function safeBase64Decode(string $input): string
    {
        $remainder = \strlen($input) % 4;

        if ((bool) $remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }

        $decoded = \base64_decode(\strtr($input, '-_', '+/'), true);

        if (false === $decoded) {
            throw new \UnexpectedValueException(
                sprintf('%s can not be decoded with the function base64_decode', $input)
            );
        }

        return $decoded;
    }
}
