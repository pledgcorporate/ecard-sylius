<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Payum\Core\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\PaymentMethodInterface;

class PaymentMethodBuilder
{
    /** @var GatewayConfigInterface */
    private $config;

    public function withConfig(GatewayConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function build(): PaymentMethodInterface
    {
        $method = new PaymentMethod();
        $method->setGatewayConfig($this->config);

        return $method;
    }
}
