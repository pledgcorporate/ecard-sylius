<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Twig\Extension;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PledgGatewayConfigReader;
use Pledg\SyliusPaymentPlugin\Refund\PledgTransferRefundService;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\PaymentInterface as CorePaymentInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PledgExtension extends AbstractExtension
{
    /** @var string[]|null */
    private ?array $pledgMethodCodes = null;

    public function __construct(
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private PledgTransferRefundService $pledgeTransferRefundService,
        private PledgGatewayConfigReader $pledgGatewayConfigReader,
        private ChannelContextInterface $channelContext,
        private SimulationInterface $simulation,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_pledg_method', [$this, 'isPledgMethod']),
            new TwigFunction('pledg_transfer_refund_available', [$this, 'isPledgTransferRefundAvailable']),
            new TwigFunction('pledg_refund_summary', [$this, 'getRefundSummary']),
            new TwigFunction('pledg_first_method_code', [$this, 'firstPledgMethodCode']),
            new TwigFunction('pledg_widget_product_enabled', [$this, 'isWidgetProductEnabled']),
            new TwigFunction('pledg_widget_checkout_enabled', [$this, 'isWidgetCheckoutEnabled']),
            new TwigFunction('pledg_widget_catalog_enabled', [$this, 'isWidgetCatalogEnabled']),
            new TwigFunction('pledg_variant_price', [$this, 'getVariantPriceCents']),
            new TwigFunction('pledg_price_in_range', [$this, 'isPriceInRange']),
            new TwigFunction('pledg_checkout_schedule', [$this, 'getCheckoutSchedule']),
        ];
    }

    public function getVariantPriceCents(?ProductVariantInterface $variant): int
    {
        if (null === $variant) {
            return 0;
        }

        try {
            $channel = $this->channelContext->getChannel();
            $channelPricing = $variant->getChannelPricingForChannel($channel);

            return $channelPricing?->getPrice() ?? 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function isPriceInRange(int $priceCents): bool
    {
        if ($priceCents <= 0) {
            return false;
        }

        $config = $this->pledgGatewayConfigReader->getMergedConfigForCurrentChannel();

        $minEur = (int) ($config[PledgGatewayFactory::PRICE_RANGE_MIN] ?? 0);
        $maxEur = (int) ($config[PledgGatewayFactory::PRICE_RANGE_MAX] ?? 0);
        $priceEur = $priceCents / 100;

        if ($minEur > 0 && $priceEur < $minEur) {
            return false;
        }
        if ($maxEur > 0 && $priceEur > $maxEur) {
            return false;
        }

        return true;
    }

    public function isWidgetProductEnabled(): bool
    {
        return $this->pledgGatewayConfigReader->isWidgetProductEnabled();
    }

    public function isWidgetCheckoutEnabled(): bool
    {
        return $this->pledgGatewayConfigReader->isWidgetCheckoutEnabled();
    }

    public function isWidgetCatalogEnabled(): bool
    {
        return $this->pledgGatewayConfigReader->isWidgetCatalogEnabled();
    }

    public function isPledgTransferRefundAvailable(CorePaymentInterface $payment): bool
    {
        if (!$this->pledgeTransferRefundService->supports($payment)) {
            return false;
        }

        return \in_array($payment->getState(), [BasePaymentInterface::STATE_COMPLETED, 'refunded'], true);
    }

    public function getRefundSummary(CorePaymentInterface $payment): array
    {
        $totalCents = (int) $payment->getAmount();
        $refundedCents = $this->pledgeTransferRefundService->getRefundedTotalCents($payment);
        $remainingCents = max(0, $totalCents - $refundedCents);
        $purchaseUid = $this->pledgeTransferRefundService->getPurchaseUid($payment);

        return [
            'refunded_cents' => $refundedCents,
            'refunded_eur' => number_format($refundedCents / 100, 2, ',', ' '),
            'remaining_cents' => $remainingCents,
            'remaining_eur' => number_format($remainingCents / 100, 2, ',', ' '),
            'total_cents' => $totalCents,
            'total_eur' => number_format($totalCents / 100, 2, ',', ' '),
            'history' => $this->pledgeTransferRefundService->getRefundHistory($payment),
            'order_items' => $this->pledgeTransferRefundService->getOrderItems($payment),
            'fully_refunded' => $remainingCents <= 0,
            'purchase_uid' => $purchaseUid,
            'has_purchase_uid' => null !== $purchaseUid,
        ];
    }

    /**
     * @return array<int, array{nb: int, caption: string, taeg_str: string, has_fees: bool, schedule: array}>
     */
    public function getCheckoutSchedule(int $amountCents, string $methodCode): array
    {
        if ($amountCents <= 0 || $methodCode === '') {
            return [];
        }

        try {
            $gwConfig = $this->pledgGatewayConfigReader->getConfigForPaymentMethodCode($methodCode);
            if (empty($gwConfig)) {
                return [];
            }

            $backUrl = $this->pledgGatewayConfigReader->getBackUrl($gwConfig);
            $merchantUid = isset($gwConfig['identifier'])
                ? trim((string) $gwConfig['identifier'])
                : '';

            if ($merchantUid === '') {
                return [];
            }

            return $this->simulation->simulateForWidget($amountCents, $merchantUid, $backUrl);
        } catch (\Throwable) {
            return [];
        }
    }

    public function firstPledgMethodCode(): ?string
    {
        $methods = $this->paymentMethodProvider->getPledgMethods();
        $first = $methods[0] ?? null;

        return $first ? (string) $first->getCode() : null;
    }

    public function isPledgMethod(string $code): bool
    {
        return in_array($code, $this->getPledgMethodCodes(), true);
    }

    private function getPledgMethodCodes(): array
    {
        if (null === $this->pledgMethodCodes) {
            $this->pledgMethodCodes = array_map(
                static function (PaymentMethodInterface $method): string {
                    return (string) $method->getCode();
                },
                $this->paymentMethodProvider->getPledgMethods()
            );
        }

        return $this->pledgMethodCodes;
    }
}
