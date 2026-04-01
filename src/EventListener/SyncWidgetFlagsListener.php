<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * When an admin saves a Pledg payment method, this listener copies
 * the widget flags + price range to every other Pledg method so they
 * stay in sync across gateways.
 */
final class SyncWidgetFlagsListener
{
    private const SYNCED_KEYS = [
        PledgGatewayFactory::WIDGET_PRODUCT_ENABLED,
        PledgGatewayFactory::WIDGET_CHECKOUT_ENABLED,
        PledgGatewayFactory::WIDGET_CATALOG_ENABLED,
        PledgGatewayFactory::PRICE_RANGE_MIN,
        PledgGatewayFactory::PRICE_RANGE_MAX,
    ];

    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof PaymentMethodInterface) {
            return;
        }

        $sourceGc = $subject->getGatewayConfig();
        if (!$sourceGc instanceof GatewayConfigInterface) {
            return;
        }
        if ($sourceGc->getFactoryName() !== PledgGatewayFactory::NAME) {
            return;
        }

        $sourceConfig = $sourceGc->getConfig();
        $valuesToSync = [];
        foreach (self::SYNCED_KEYS as $key) {
            if (\array_key_exists($key, $sourceConfig)) {
                $valuesToSync[$key] = $sourceConfig[$key];
            }
        }

        if (empty($valuesToSync)) {
            return;
        }

        $allMethods = $this->paymentMethodRepository->findAll();
        foreach ($allMethods as $method) {
            if (!$method instanceof PaymentMethodInterface) {
                continue;
            }
            if ($method === $subject) {
                continue;
            }

            $gc = $method->getGatewayConfig();
            if (!$gc instanceof GatewayConfigInterface) {
                continue;
            }
            if ($gc->getFactoryName() !== PledgGatewayFactory::NAME) {
                continue;
            }

            $targetConfig = $gc->getConfig();
            $changed = false;
            foreach ($valuesToSync as $key => $value) {
                if (!isset($targetConfig[$key]) || $targetConfig[$key] !== $value) {
                    $targetConfig[$key] = $value;
                    $changed = true;
                }
            }

            if ($changed) {
                $gc->setConfig($targetConfig);
            }
        }

        $this->entityManager->flush();
    }
}
