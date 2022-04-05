<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Payum\Core\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\PaymentMethodInterface;

class PaymentMethodBuilder
{
    /** @var string */
    private $name;

    /** @var GatewayConfigInterface */
    private $config;

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withConfig(GatewayConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function build(): PaymentMethodInterface
    {
        $method = new PaymentMethod();
        $method->setCurrentLocale('fr_FR');
        $method->setName($this->name);
        $method->setGatewayConfig($this->config);

        return $method;
    }
}
