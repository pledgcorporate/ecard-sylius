<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

class InMemoryPaymentRepository implements PaymentRepositoryInterface
{
    protected $payments = [];

    public function find($id)
    {
        if (!isset($this->payments[$id])) {
            throw new EntityNotFoundException(sprintf('The payment %s does not exist', $id));
        }

        return $this->payments[$id];
    }

    public function add(ResourceInterface $resource): void
    {
        $this->payments[$resource->getId()] = $resource;
    }

    public function remove(ResourceInterface $resource): void
    {
        unset($this->payments[$resource->getId()]);
    }

    public function findAll()
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function findOneBy(array $criteria)
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function getClassName()
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function createListQueryBuilder(): QueryBuilder
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function findOneByCustomer($id, CustomerInterface $customer): ?PaymentInterface
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function findOneByOrderId($paymentId, $orderId): ?PaymentInterface
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function findOneByOrderToken(string $paymentId, string $orderToken): ?PaymentInterface
    {
        throw new \RuntimeException('not implemented yet');
    }

    public function createPaginator(array $criteria = [], array $sorting = []): iterable
    {
        throw new \RuntimeException('not implemented yet');
    }
}
