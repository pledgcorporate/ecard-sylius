<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\PaymentSchedule;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\PaymentSchedule;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

class SimulationApi implements SimulationInterface
{
    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $pledgUrl;

    private const ROUTE = '/api/users/me/merchants/<merchant_uid>/simulate_payment_schedule';

    public function __construct(ClientInterface $client, string $pledgUrl)
    {
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

            return PaymentSchedule::fromArray(
                json_decode($response->getBody()->getContents(), true)['INSTALLMENT']
            );
        } catch (GuzzleException $e) {
            return new PaymentSchedule();
        }
    }
}
