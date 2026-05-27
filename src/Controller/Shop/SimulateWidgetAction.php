<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Shop;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PledgGatewayConfigReader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $methods = $this->paymentMethodProvider->getPledgMethods();
        $method = $methods[0] ?? null;
        if (null === $method) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $code = (string) $method->getCode();
        $merchant = $this->merchantProvider->findByMethod($method);
        $scheduleUid = $this->pledgGatewayConfigReader->getScheduleMerchantUidForPaymentMethodCode($code);
        $gwConfig = $this->pledgGatewayConfigReader->getConfigForPaymentMethodCode($code);
        $backUrl = $this->pledgGatewayConfigReader->getBackUrl($gwConfig);

        $uid = $scheduleUid ?? $merchant->getIdentifier();

        $result = [];
        $seen = [];

        foreach ($amounts as $amount) {
            $key = (string) (int) $amount;
            if (isset($seen[$key]) || (int) $amount <= 0) {
                continue;
            }
            $seen[$key] = true;

            try {
                $result[$key] = $this->simulation->simulateForWidget((int) $amount, $uid, $backUrl);
            } catch (\Throwable $e) {
                $this->logger->warning('Widget batch simulation error', [
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);
                $result[$key] = [];
            }
        }

        return new JsonResponse($result);
    }
}
