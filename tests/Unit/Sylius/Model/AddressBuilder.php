<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;


use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\AddressInterface;

class AddressBuilder
{
    /** @var string */
    protected $street;

    /** @var string */
    protected $city;

    /** @var string */
    protected $zipcode;

    /** @var string */
    protected $countryCode;

    public function __construct()
    {
        $this->street = 'a random street';
        $this->city = 'Paris';
        $this->zipcode = '75015';
        $this->countryCode = 'FR';
    }

    public function build(): AddressInterface
    {
        $address = new Address();
        $address->setStreet($this->street);
        $address->setCity($this->city);
        $address->setPostcode($this->zipcode);
        $address->setCountryCode($this->countryCode);

        return $address;
    }
}
