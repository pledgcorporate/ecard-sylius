<?php

namespace Tests\Pledg\SyliusPaymentPlugin\PaymentSchedule;

use GuzzleHttp\ClientInterface;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationApi;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SimulationBuilder
{
    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $url = '';

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
            $this->url
        );
    }
}