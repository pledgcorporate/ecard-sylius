<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\PaymentSchedule;

use GuzzleHttp\ClientInterface;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationApi;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class SimulationBuilder
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $url = '';

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function withUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function withSimulation(array $simulation): self
    {
        $body = (new Prophet())->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(json_encode($simulation));
        $response = (new Prophet())->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($body->reveal());
        $client = (new Prophet())->prophesize(ClientInterface::class);
        $client->request(Argument::cetera())->willReturn($response->reveal());
        $this->client = $client->reveal();

        return $this;
    }

    public function build(): SimulationInterface
    {
        return new SimulationApi(
            $this->client,
            $this->url,
            $this->logger ?: (new Prophet())->prophesize(LoggerInterface::class)->reveal()
        );
    }
}
