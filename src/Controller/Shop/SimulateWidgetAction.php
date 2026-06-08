<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Shop;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PledgGatewayConfigReader;
use Pledg\SyliusPaymentPlugin\Resolver\PaymentMethodsResolver;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;

/**
 * Batch simulation endpoint for the Sofinco widget.
 * Accepts multiple amounts and returns formatted display items per amount.
 *
 * POST /sylius/pledg/widget/simulate-batch
 * Body: {"amounts": [5000, 7500, 29500]}
 * Response: {"5000": [{nb, caption, schedule, taeg_str, ...}], ...}
 */
final class SimulateWidgetAction
{
    public function __construct(
        private SimulationInterface $simulation,
        private MerchantProviderInterface $merchantProvider,
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private PledgGatewayConfigReader $pledgGatewayConfigReader,
        private PaymentMethodsResolver $paymentMethodsResolver,
        private ChannelContextInterface $channelContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse(['error' => 'POST required'], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        try {
            $body = json_decode((string) $request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $amounts = $body['amounts'] ?? [];
        if (!\is_array($amounts) || [] === $amounts) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $result = [];

        $eligibleMethods = $this->getWidgetEligibleMethods();

        foreach ($eligibleMethods as $eligibleMethod) {

            $method = $eligibleMethod['method'];
            $methodGatewayConfig = $eligibleMethod['gatewayConfig'];

            $code = (string) $method->getCode();
            $merchant = $this->merchantProvider->findByMethod($method);
            $scheduleUid = $this->pledgGatewayConfigReader->getScheduleMerchantUidForPaymentMethodCode($code);

            $backUrl = $this->pledgGatewayConfigReader->getBackUrl($methodGatewayConfig);

            $uid = $scheduleUid ?? $merchant->getIdentifier();

            foreach ($amounts as $amount) {

                // method max price must be unset or superior to the amount
                $methodMaxPrice = $methodGatewayConfig[PledgGatewayFactory::PRICE_RANGE_MAX];
                if (true === empty($methodMaxPrice)) {
                    continue;
                }
                $methodMaxPrice = 100 * $methodMaxPrice;

                if (
                    $methodMaxPrice < $amount
                ) {
                    continue;
                }

                // method min price must be unset or inferior to the amount
                $methodMinPrice = $methodGatewayConfig[PledgGatewayFactory::PRICE_RANGE_MIN];
                if (true === empty($methodMinPrice)) {
                    continue;
                }
                $methodMinPrice = 100 * $methodMinPrice;

                if (
                    $methodMinPrice > $amount
                ) {
                    continue;
                }

                $key = (string) (int) $amount;

                if (false === array_key_exists($key, $result)) {
                    $result[$key] = [];
                }

                try {
                    $result[$key][$code] = $this->simulation->simulateForWidget((int) $amount, $uid, $backUrl);
                } catch (\Throwable $e) {
                    $this->logger->warning('Widget batch simulation error', [
                        'amount'    => $amount,
                        'code'       => $code,
                        'error'     => $e->getMessage(),
                    ]);
                    $result[$key][$code] = [];
                }
            }
        }

        return new JsonResponse($result);
    }

    private function getWidgetEligibleMethods()
    {
        $result = [];

        $pledgMethods = $this->paymentMethodProvider->getPledgMethods();

        $currentChannel = $this->channelContext->getChannel();

        foreach ($pledgMethods as $method) {

            // method must be actvated
            if (true !== $method->isEnabled()) {
                continue;
            }

            // method must be in the current channel
            $foundCurrentChannel = false;
            $methodChannels = $method->getChannels();
            foreach ($methodChannels as $channel) {
                if ($channel->getCode() === $currentChannel->getCode()) {
                    $foundCurrentChannel = true;
                    break;
                }
            }

            if (false === $foundCurrentChannel) {
                continue;
            }

            // method identifier must be set
            $code = $method->getCode();
            $methodGatewayConfig = $this->pledgGatewayConfigReader->getConfigForPaymentMethodCode($code);

            $methodIdentifier = $methodGatewayConfig[PledgGatewayFactory::IDENTIFIER];
            if (
                null === $methodIdentifier
            ||  true === empty(trim($methodIdentifier))
            ) {
                continue;
            }

            // method secret must be set
            $methodSecret = $methodGatewayConfig[PledgGatewayFactory::SECRET];
            if (
                null === $methodSecret
            ||  true === empty(trim($methodSecret))
            ) {
                continue;
            }

            // method must have at least one of the 3 widget display options:
            // - WIDGET_PRODUCT_ENABLED
            // - WIDGET_CHECKOUT_ENABLED
            // - WIDGET_CATALOG_ENABLED
            $widgetLocations = [
                PledgGatewayFactory::WIDGET_PRODUCT_ENABLED,
                PledgGatewayFactory::WIDGET_CHECKOUT_ENABLED,
                PledgGatewayFactory::WIDGET_CATALOG_ENABLED,
            ];

            foreach ($widgetLocations as $location) {
                if (
                    true === $methodGatewayConfig[$location]
                &&  false === array_key_exists($code, $result)
                ) {
                    $result[$methodGatewayConfig['identifier']] = [
                        'method'        => $method,
                        'gatewayConfig' => $methodGatewayConfig,
                    ];
                }
            }
        }

        return $result;
    }
}
