<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\PaymentSchedule;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Psr\Log\LoggerInterface;

class SimulationApi implements SimulationInterface
{
    private const ROUTE = '/api/users/me/merchants/<merchant_uid>/simulate_payment_schedule';
    private const COMPANY_ROUTE = '/api/users/me/companies/<company_uid>/simulate_payment_schedule';

    public function __construct(
        private ClientInterface $client,
        private string $pledgUrl,
        private LoggerInterface $logger,
    ) {
    }

    public function simulate(MerchantInterface $merchant, int $amount, \DateTimeInterface $createdAt, ?string $scheduleMerchantUid = null, ?string $backUrlOverride = null): PaymentSchedule
    {
        $baseUrl = $backUrlOverride ?? $this->pledgUrl;

        $uidsToTry = [];
        if (null !== $scheduleMerchantUid && $scheduleMerchantUid !== '') {
            $uidsToTry[] = $scheduleMerchantUid;
        }
        $merchantId = $merchant->getIdentifier();
        if (!\in_array($merchantId, $uidsToTry, true)) {
            $uidsToTry[] = $merchantId;
        }

        foreach ($uidsToTry as $uid) {
            $result = $this->doSimulate($uid, $amount, $createdAt, $baseUrl);
            if (!$result->isEmpty()) {
                return $result;
            }
        }

        return new PaymentSchedule();
    }

    private function resolveRoute(string $uid): string
    {
        if (str_starts_with($uid, 'cmp_')) {
            return str_replace('<company_uid>', $uid, self::COMPANY_ROUTE);
        }

        return str_replace('<merchant_uid>', $uid, self::ROUTE);
    }

    private function doSimulate(string $uid, int $amount, \DateTimeInterface $createdAt, string $baseUrl): PaymentSchedule
    {
        try {
            $url = $baseUrl . $this->resolveRoute($uid);
            $response = $this->client->request('POST', $url, [
                'body' => json_encode([
                    'amount_cents' => $amount,
                    'created' => $createdAt->format('Y-m-d'),
                ]),
            ]);

            /** @var array $content */
            $content = json_decode($response->getBody()->getContents(), true);

            if (!isset($content['INSTALLMENT']) && !isset($content['DEFERRED']) && !isset($content['items'])) {
                $this->logger->warning('Pledg simulation: no schedule data', [
                    'merchant_id' => $uid,
                    'amount' => $amount,
                ]);

                return new PaymentSchedule();
            }

            if (isset($content['items'])) {
                $firstItem = $content['items'][0] ?? [];
                $installments = $firstItem['INSTALLMENT'] ?? $firstItem['installment'] ?? [];
                if (!empty($installments)) {
                    return PaymentSchedule::fromArray($installments);
                }

                return new PaymentSchedule();
            }

            $deferredSchedule = isset($content['DEFERRED']) ? [$content['DEFERRED']] : [];
            $standardSchedule = $content['INSTALLMENT'] ?? [];

            return PaymentSchedule::fromArray(
                $deferredSchedule !== [] ? $deferredSchedule : $standardSchedule
            );
        } catch (GuzzleException $e) {
            $this->logger->warning('Pledg simulation failed for ' . $uid, [
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return new PaymentSchedule();
        }
    }
}
