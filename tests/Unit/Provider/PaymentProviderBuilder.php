<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Provider;


use Pledg\SyliusPaymentPlugin\Provider\PaymentProvider;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;

class PaymentProviderBuilder
{
    /** @var PaymentRepositoryInterface  */
    private $repository;

    public function __construct()
    {
        $this->repository = new InMemoryPaymentRepository();
    }

    public function withRepository(PaymentRepositoryInterface $paymentRepository): self
    {
        $this->repository = $paymentRepository;

        return $this;
    }

    public function build(): PaymentProviderInterface
    {
        return new PaymentProvider($this->repository);
    }
}
