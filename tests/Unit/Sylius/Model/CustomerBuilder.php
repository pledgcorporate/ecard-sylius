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

    /** @var int */
    protected $id;

    protected $createdAt;

    public function __construct()
    {
        $this->id = 1;
        $this->firstName = 'John';
        $this->lastName = 'Doe';
    }

    public function withId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function build(): CustomerInterface
    {
        $customer = new Customer();
        $customer->setFirstName($this->firstName);
        $customer->setLastName($this->lastName);
        $customer->setCreatedAt($this->createdAt);
        $reflectionClass = new \ReflectionClass(Customer::class);
        $id = $reflectionClass->getProperty('id');
        $id->setAccessible(true);
        $id->setValue($customer, $this->id);

        return $customer;
    }
}
