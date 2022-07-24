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

    private const ROUTE = '/api/users/me/merchants/<merchant_uid>/simulate_payment_schedule';

    public function __construct(ClientInterface $client, string $pledgUrl, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->pledgUrl = $pledgUrl;
    }

    public function simulate(MerchantInterface $merchant, int $amount, \DateTimeInterface $createdAt): PaymentSchedule
    {
        try {
            $response = $this->client->request(
                'POST',
                sprintf(
                    '%s%s',
                    $this->pledgUrl,
                    str_replace('<merchant_uid>', $merchant->getIdentifier(), self::ROUTE, )
                ),
                [
                    'body' => json_encode([
                        'amount_cents' => $amount,
                        'created' => $createdAt->format('Y-m-d'),
                    ]),
                ]
            );
            $content = json_decode($response->getBody()->getContents(), true);

            if (!isset($content['INSTALLMENT'])) {
                $this->logger->error('the simulation call has failed', [
                    'merchant_id' => $merchant->getIdentifier(),
                    'amount' => $amount,
                    'created_at' => $createdAt->format('Y-m-d'),
                    'content' => $content,
                ]);

                return new PaymentSchedule();
            }

            return PaymentSchedule::fromArray(
                $content['INSTALLMENT']
            );
        } catch (GuzzleException $e) {
            $this->logger->error('the simulation call has failed', [
                'merchant_id' => $merchant->getIdentifier(),
                'amount' => $amount,
                'created_at' => $createdAt->format('Y-m-d'),
                'exception_message' => $e->getMessage(),
            ]);

            return new PaymentSchedule();
        }
    }
}
