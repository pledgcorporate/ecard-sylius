<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Resolver;

use Payum\Core\Model\GatewayConfigInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;

class PaymentMethodsResolver implements PaymentMethodsResolverInterface
{
    /** @var PaymentMethodsResolverInterface */
    private $paymentMethodResolver;

    public function __construct(PaymentMethodsResolverInterface $paymentMethodResolver)
    {
        $this->paymentMethodResolver = $paymentMethodResolver;
    }

    public function getSupportedMethods(PaymentInterface $subject): array
    {
        return array_filter(
            $this->paymentMethodResolver->getSupportedMethods($subject),
            static function (PaymentMethodInterface $method): bool {
                return !$method->getGatewayConfig() instanceof GatewayConfigInterface
                    || $method->getGatewayConfig()->getFactoryName() !== PledgGatewayFactory::NAME;
            }
        );
    }

    public function supports(PaymentInterface $subject): bool
    {
        return $this->paymentMethodResolver->supports($subject);
    }
}
