<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Doctrine\ORM\EntityRepository;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;

class PaymentMethodProvider implements PaymentMethodProviderInterface
{
    /** @var EntityRepository */
    private $repository;

    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getPledgMethods(): array
    {
        return $this->repository->createQueryBuilder('o')
            ->innerJoin('o.gatewayConfig', 'gatewayConfig')
            ->where('gatewayConfig.factoryName = :factoryName')
            ->setParameter('factoryName', PledgGatewayFactory::NAME)
            ->getQuery()
            ->getResult();
    }
}
