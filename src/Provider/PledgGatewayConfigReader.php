<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\PledgUrl;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PledgGatewayConfigReader
{
    public function __construct(
        private ChannelContextInterface $channelContext,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedConfigForCurrentChannel(): array
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (\Throwable) {
            return [];
        }

        if (!$channel instanceof ChannelInterface) {
            return [];
        }

        $merged = [];

        try {
            $methods = $this->paymentMethodRepository->findEnabledForChannel($channel);
        } catch (\Throwable) {
            $methods = [];
        }

        foreach ($methods as $method) {
            if (!$method instanceof PaymentMethodInterface) {
                continue;
            }
            $gc = $method->getGatewayConfig();
            if (null === $gc || $gc->getFactoryName() !== PledgGatewayFactory::NAME) {
                continue;
            }
            $merged = array_merge($merged, $gc->getConfig());
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigForPaymentMethodCode(string $code): array
    {
        $method = $this->paymentMethodRepository->findOneBy(['code' => $code]);
        if (!$method instanceof PaymentMethodInterface) {
            return [];
        }
        $gc = $method->getGatewayConfig();
        if (null === $gc || $gc->getFactoryName() !== PledgGatewayFactory::NAME) {
            return [];
        }

        return $gc->getConfig();
    }

    public function getScheduleMerchantUidForPaymentMethodCode(string $code): ?string
    {
        $config = $this->getConfigForPaymentMethodCode($code);
        $company = isset($config[PledgGatewayFactory::COMPANY_UID]) ? trim((string) $config[PledgGatewayFactory::COMPANY_UID]) : '';

        return $company !== '' ? $company : null;
    }

    public function isWidgetProductEnabled(): bool
    {
        return $this->widgetFlag(PledgGatewayFactory::WIDGET_PRODUCT_ENABLED, true);
    }

    public function isWidgetCheckoutEnabled(): bool
    {
        return $this->widgetFlag(PledgGatewayFactory::WIDGET_CHECKOUT_ENABLED, false);
    }

    public function isWidgetCatalogEnabled(): bool
    {
        return $this->widgetFlag(PledgGatewayFactory::WIDGET_CATALOG_ENABLED, false);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function isSandbox(array $config): bool
    {
        if (!\array_key_exists(PledgGatewayFactory::SANDBOX, $config)) {
            return true;
        }

        return $this->truthy($config[PledgGatewayFactory::SANDBOX]);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getFrontUrl(array $config): string
    {
        $url = PledgUrl::frontUrl($this->isSandbox($config));

        $this->logger->debug('PLEDG ' . __METHOD__, compact('config'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('url'));

        return $url;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getBackUrl(array $config): string
    {
        $url = PledgUrl::backUrl($this->isSandbox($config));

        $this->logger->debug('PLEDG ' . __METHOD__, compact('config'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('url'));

        return $url;
    }

    private function widgetFlag(string $key, bool $defaultIfMissing): bool
    {
        $configs = $this->getAllPledgConfigsForCurrentChannel();
        if (empty($configs)) {
            return $defaultIfMissing;
        }

        foreach ($configs as $config) {
            if (\array_key_exists($key, $config) && $this->truthy($config[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAllPledgConfigsForCurrentChannel(): array
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (\Throwable) {
            return [];
        }

        if (!$channel instanceof ChannelInterface) {
            return [];
        }

        try {
            $methods = $this->paymentMethodRepository->findEnabledForChannel($channel);
        } catch (\Throwable) {
            return [];
        }

        $configs = [];
        foreach ($methods as $method) {
            if (!$method instanceof PaymentMethodInterface) {
                continue;
            }
            $gc = $method->getGatewayConfig();
            if (null === $gc || $gc->getFactoryName() !== PledgGatewayFactory::NAME) {
                continue;
            }
            $configs[] = $gc->getConfig();
        }

        return $configs;
    }

    private function truthy(mixed $v): bool
    {
        if (\is_bool($v)) {
            return $v;
        }
        if (\is_string($v)) {
            return \in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $v;
    }
}
