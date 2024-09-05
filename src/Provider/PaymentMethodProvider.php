<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Doctrine\ORM\EntityRepository;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Sylius\Component\Core\Model\PaymentMethodInterface;

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
        /** @var PaymentMethodInterface[] $res */
        $res = $this->repository->createQueryBuilder('o')
            ->innerJoin('o.gatewayConfig', 'gatewayConfig')
            ->where('gatewayConfig.factoryName = :factoryName')
            ->setParameter('factoryName', PledgGatewayFactory::NAME)
            ->getQuery()
            ->getResult();

        return $res;
    }

    public function findOneByCode(string $code): PaymentMethodInterface
    {
        /** @var PaymentMethodInterface $res */
        $res = $this->repository->createQueryBuilder('o')
            ->andWhere('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleResult();

        return $res;
    }
}
