<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\PaymentSchedule;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\Payment;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\DTO\PaymentSchedule;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class SimulationApiTest extends TestCase
{
    /** @test */
    public function it_handle_simulation_when_the_response_is_correct(): void
    {
        $rawSchedule = [
            [
                'payment_date' => '2022-02-02',
                'amount_cents' => 100,
                'fees' => 10,
            ],
            [
                'payment_date' => '2022-03-02',
                'amount_cents' => 100,
                'fees' => 2,
            ],
        ];
        $simulation = (new SimulationBuilder())->withSimulation(['INSTALLMENT' => $rawSchedule])->build();

        $schedule = $simulation->simulate((new MerchantBuilder())->build(), 10, new \DateTimeImmutable());

        self::assertInstanceOf(PaymentSchedule::class, $schedule);
        self::assertCount(2, $schedule->payments);
        self::assertSame(12, $schedule->getFees());
        self::assertSame($rawSchedule, array_map(function (Payment $paymentDto) {
            return [
                'payment_date' => $paymentDto->date->format('Y-m-d'),
                'amount_cents' => $paymentDto->amount,
                'fees' => $paymentDto->fees,
            ];
        }, $schedule->payments));
    }

    /** @test */
    public function it_return_empty_simulation_when_api_call_throw_exception(): void
    {
        $client = $this->prophesize(ClientInterface::class);
        $client->request(Argument::cetera())->willThrow(new InvalidArgumentException());

        $simulation = (new SimulationBuilder())
            ->withClient($client->reveal())
            ->build();

        $schedule = $simulation->simulate((new MerchantBuilder())->build(), 10, new \DateTimeImmutable());

        self::assertInstanceOf(PaymentSchedule::class, $schedule);
        self::assertCount(0, $schedule->payments);
    }

    /** @test */
    public function it_log_an_error_when_api_call_throw_exception(): void
    {
        $merchant = (new MerchantBuilder())->withIdentifier('identifier')->build();
        $amount = 10;
        $createdAt = new \DateTimeImmutable();
        $message = 'the simulation call has failed';
        $logger = $this->prophesize(LoggerInterface::class);

        $logger->error($message, [
            'merchant_id' => $merchant->getIdentifier(),
            'amount' => $amount,
            'created_at' => $createdAt->format('Y-m-d'),
            'exception_message' => null,
        ])->shouldBeCalled();

        $client = $this->prophesize(ClientInterface::class);
        $client->request(Argument::cetera())->willThrow(new InvalidArgumentException());

        $simulation = (new SimulationBuilder())
            ->withLogger($logger->reveal())
            ->withClient($client->reveal())
            ->build();

        $simulation->simulate($merchant, 10, $createdAt);
    }
}
