<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;


use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;

class MerchantBuilder
{
    public const VALID_IDENTIFIER = 'mer_aee4846c-ac62-4835-8adf-bea9f8737144';
    public const VALID_SECRET = 'aIDZLuoAdK8NAqoFIFPBao72WEQ6jrWMvYwaXaiO';

    /** @var string */
    protected $identifier;
    /** @var string */
    protected $secret;

    public function __construct()
    {
        $this->identifier = self::VALID_IDENTIFIER;
        $this->secret = self::VALID_SECRET;
    }

    public function build(): Merchant
    {
        return new Merchant($this->identifier, $this->secret);
    }
}
