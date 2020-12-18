<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Parser;

/**
 * Encode parameters with JWT HS256 algorithm
 */
class Encoder implements EncoderInterface
{
    public function encode(array $parameters, string $secret): string
    {
        // Use HS256 to generate and parse tokens
        //$signer = new HS256($secret);

        // Generate a token
        //$generator = new Generator($signer);
        //return $generator->generate($parameters);

        $segments = [
            $this->safeBase65Encode(json_encode(['alg'=>'HS256','typ'=>'JWT'], JSON_THROW_ON_ERROR)), //build the headers,
            $this->safeBase65Encode(json_encode($parameters, JSON_THROW_ON_ERROR)), // build Parameters
        ];

        $segments[] = hash_hmac('sha256',implode('.', $segments),$secret); // build the signature

        return implode('.', $segments);
    }

    public function decode(string $token): array
    {
        $encodedParameters = explode('.', $token)[1];

        return json_decode($this->safeBase64Decode($encodedParameters), true, 512, JSON_THROW_ON_ERROR);

    }

    private function safeBase65Encode(string $input): string
    {
        //return \base64_encode($input);
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
