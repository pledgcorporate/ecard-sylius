<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Payum\Core\Model\GatewayConfig;
use Payum\Core\Model\GatewayConfigInterface;

class GatewayConfigBuilder
{
    /** @var string */
    private $name;

    /** @var array */
    private $config = [];

    public function withFactoryName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withConfig(string $name, $value): self
    {
        $this->config[$name] = $value;

        return $this;
    }

    public function build(): GatewayConfigInterface
    {
        $config = new GatewayConfig();
        $config->setFactoryName($this->name);
        $config->setConfig($this->config);

        return $config;
    }
}
