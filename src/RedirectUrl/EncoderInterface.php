<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;


interface EncoderInterface
{
    public function encode(array $parameters, string $secret): string;
}
