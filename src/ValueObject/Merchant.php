<?php


namespace Pledg\SyliusPaymentPlugin\ValueObject;


class Merchant implements MerchantInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $secret;

    public function __construct(string $identifier, string $secret)
    {
        $this->identifier = $identifier;
        $this->secret = $secret;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
