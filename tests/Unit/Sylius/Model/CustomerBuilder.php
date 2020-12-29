<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;

class CustomerBuilder
{
    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    public function __construct()
    {
        $this->firstName = 'John';
        $this->lastName = 'Doe';
    }

    public function build(): CustomerInterface
    {
        $customer = new Customer();
        $customer->setFirstName($this->firstName);
        $customer->setLastName($this->lastName);

        return $customer;
    }
}
