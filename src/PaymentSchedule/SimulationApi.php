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
    /** @var LoggerInterface */
    private $logger;

    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $pledgUrl;

    /** @var array<string, array{0: array, 1: float}> */
    private static array $widgetCache = [];

    private const ROUTE = '/api/users/me/merchants/<merchant_uid>/simulate_payment_schedule';
    private const COMPANY_ROUTE = '/api/users/me/companies/<company_uid>/simulate_payment_schedule';
    private const CACHE_TTL = 600;

    public function __construct(ClientInterface $client, string $pledgUrl, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->pledgUrl = $pledgUrl;
    }

    public function simulate(MerchantInterface $merchant, int $amount, \DateTimeInterface $createdAt, ?string $scheduleMerchantUid = null, ?string $backUrlOverride = null): PaymentSchedule
    {
        $baseUrl = $backUrlOverride ?? $this->pledgUrl;

        $uidsToTry = [];
        if (null !== $scheduleMerchantUid) {
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

    public function simulateForWidget(int $amountCents, string $uid, string $baseUrl): array
    {
        $cacheKey = $uid . ':' . $amountCents;
        if (isset(self::$widgetCache[$cacheKey])) {
            [$cached, $ts] = self::$widgetCache[$cacheKey];
            if ((microtime(true) - $ts) < self::CACHE_TTL) {
                return $cached;
            }
        }

        try {
            $url = $baseUrl . $this->resolveRoute($uid);
            $response = $this->client->request('POST', $url, [
                'body' => json_encode([
                    'amount_cents' => $amountCents,
                    'created' => (new \DateTimeImmutable())->format('Y-m-d'),
                ]),
            ]);

            $content = json_decode($response->getBody()->getContents(), true);
            $items = $this->formatWidgetItems($content ?? []);

            self::$widgetCache[$cacheKey] = [$items, microtime(true)];
            if (\count(self::$widgetCache) > 500) {
                $oldest = array_keys(self::$widgetCache);
                foreach (\array_slice($oldest, 0, 200) as $k) {
                    unset(self::$widgetCache[$k]);
                }
            }

            return $items;
        } catch (\Throwable $e) {
            $this->logger->warning('Pledg widget simulation failed', [
                'uid' => $uid,
                'amount' => $amountCents,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function resolveRoute(string $uid): string
    {
        if (str_starts_with($uid, 'cmp_')) {
            return str_replace('<company_uid>', $uid, self::COMPANY_ROUTE);
        }

        return str_replace('<merchant_uid>', $uid, self::ROUTE);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatWidgetItems(array $data): array
    {
        if (isset($data['items']) && \is_array($data['items'])) {
            $items = [];
            foreach ($data['items'] as $item) {
                $formatted = $this->formatInstallmentItem($item);
                if (null !== $formatted) {
                    $items[] = $formatted;
                }
            }
            usort($items, static fn (array $a, array $b) => $a['nb'] <=> $b['nb']);

            return $items;
        }

        $installments = $data['INSTALLMENT'] ?? [];
        if (!empty($installments)) {
            $formatted = $this->formatInstallmentItem(['INSTALLMENT' => $installments]);
            if (null !== $formatted) {
                return [$formatted];
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatInstallmentItem(array $item): ?array
    {
        $installments = $item['INSTALLMENT'] ?? $item['installment'] ?? [];
        if (empty($installments)) {
            return null;
        }

        $arr = \is_array($installments) ? array_values($installments) : [$installments];
        $nb = \count($arr);
        $totalFees = 0;
        $schedule = [];

        foreach ($arr as $idx => $inst) {
            $amtCents = (int) ($inst['amount_cents'] ?? 0);
            $feesCents = (int) ($inst['fees'] ?? 0);
            $totalAmount = ($amtCents + $feesCents) / 100;
            $feesAmount = $feesCents / 100;
            $totalFees += $feesCents;

            $schedule[] = [
                'n' => $idx + 1,
                'date' => $inst['payment_date'] ?? '',
                'amount' => self::fmtPrice($totalAmount),
                'amount_raw' => $totalAmount,
                'fees' => $feesCents > 0 ? self::fmtPrice($feesAmount) : '',
                'fees_raw' => $feesAmount,
            ];
        }

        $taeg = $item['taeg'] ?? $item['debit_rate'] ?? null;
        $hasFees = $totalFees > 0 || ($taeg !== null && (float) $taeg > 0);

        $firstAmt = $schedule[0]['amount'] ?? '0';
        $secondAmt = $schedule[1]['amount'] ?? $firstAmt;
        $caption = $nb > 1
            ? sprintf('Payez %s € puis %d × %s €', $firstAmt, $nb - 1, $secondAmt)
            : sprintf('Payez %s €', $firstAmt);

        return [
            'nb' => $nb,
            'caption' => $caption,
            'taeg' => $taeg,
            'taeg_str' => $taeg !== null ? number_format((float) $taeg, 2) : '',
            'has_fees' => $hasFees,
            'total_fees' => $totalFees > 0 ? self::fmtPrice($totalFees / 100) : '',
            'schedule' => $schedule,
        ];
    }

    private static function fmtPrice(float $val): string
    {
        return number_format($val, 2, ',', '');
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
                    'content' => $content,
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
