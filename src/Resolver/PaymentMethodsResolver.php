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
        /** @var int $amount */
        $amount = $subject->getAmount();

        return array_merge(
            $this->getPledgMethods(
                $methods,
                $this->getBillingCountryCode($subject),
                $amount
            ),
            $this->getOtherMethods($methods)
        );
    }

    private function getOtherMethods(array $methods): array
    {
        return array_filter($methods, [$this, 'isNotPledgType']);
    }

    private function getPledgMethods(array $methods, string $country, int $amount): array
    {
        $that = $this;

        return array_filter(
            $methods,
            static function (PaymentMethodInterface $method) use ($country, $amount, $that): bool {
                return $that->isPledgType($method)
                    && $that->billingCountryIsInPledgRestrictedCountries($method, $country)
                    && $that->amountIsInPledgPriceRange($method, $amount);
            }
        );
    }

    private function isPledgType(PaymentMethodInterface $method): bool
    {
        return $method->getGatewayConfig() instanceof GatewayConfigInterface
            && $method->getGatewayConfig()->getFactoryName() === PledgGatewayFactory::NAME;
    }

    private function isNotPledgType(PaymentMethodInterface $method): bool
    {
        return !$this->isPledgType($method);
    }

    private function billingCountryIsInPledgRestrictedCountries(PaymentMethodInterface $method, string $country): bool
    {
        return $method->getGatewayConfig() instanceof GatewayConfigInterface
            && in_array(
                $country,
                $method->getGatewayConfig()->getConfig()[PledgGatewayFactory::RESTRICTED_COUNTRIES],
                true
            );
    }

    private function amountIsInPledgPriceRange(PaymentMethodInterface $method, int $amount): bool
    {
        return $method->getGatewayConfig() instanceof GatewayConfigInterface
            && (
                $this->getMinimumPrice($method->getGatewayConfig()) === null
                || $amount >= $this->getMinimumPrice($method->getGatewayConfig())
            )
            && (
                $this->getMaximumPrice($method->getGatewayConfig()) === null
                || $amount <= $this->getMaximumPrice($method->getGatewayConfig())
            );
    }

    private function getMinimumPrice(GatewayConfigInterface $config): ?int
    {
        return $config->getConfig()[PledgGatewayFactory::PRICE_RANGE_MIN]
            ? (int) (100 * $config->getConfig()[PledgGatewayFactory::PRICE_RANGE_MIN])
            : null;
    }

    private function getMaximumPrice(GatewayConfigInterface $config): ?int
    {
        return $config->getConfig()[PledgGatewayFactory::PRICE_RANGE_MAX]
            ? (int) (100 * $config->getConfig()[PledgGatewayFactory::PRICE_RANGE_MAX])
            : null;
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
