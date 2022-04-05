<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Resolver;

use Payum\Core\Model\GatewayConfigInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
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
        $methods = $this->paymentMethodResolver->getSupportedMethods($subject);

        $restrictedMethods = $this->getMethodsRestrictedByCountry(
            $methods,
            $this->getBillingCountryCode($subject)
        );

        return array_merge(
            $restrictedMethods,
            $this->getOtherMethods($methods)
        );
    }

    private function getOtherMethods(array $methods): array
    {
        return array_filter(
            $methods,
            static function (PaymentMethodInterface $method): bool {
                return !$method->getGatewayConfig() instanceof GatewayConfigInterface
                    || $method->getGatewayConfig()->getFactoryName() !== PledgGatewayFactory::NAME;
            }
        );
    }

    private function getMethodsRestrictedByCountry(array $methods, string $country): array
    {
        return array_filter(
            $methods,
            static function (PaymentMethodInterface $method) use ($country): bool {
                return $method->getGatewayConfig() instanceof GatewayConfigInterface
                    && $method->getGatewayConfig()->getFactoryName() === PledgGatewayFactory::NAME
                    && in_array(
                        $country,
                        $method->getGatewayConfig()->getConfig()[PledgGatewayFactory::RESTRICTED_COUNTRIES],
                        true
                    );
            }
        );
    }

    private function getBillingCountryCode(PaymentInterface $subject): string
    {
        /** @var \Sylius\Component\Core\Model\PaymentInterface $payment */
        $payment = $subject;
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var AddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        /** @var string $country */
        $country = $billingAddress->getCountryCode();

        return $country;
    }

    public function supports(PaymentInterface $subject): bool
    {
        return $this->paymentMethodResolver->supports($subject);
    }
}
