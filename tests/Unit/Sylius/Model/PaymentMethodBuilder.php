<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model;

use Payum\Core\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\PaymentMethodInterface;

class PaymentMethodBuilder
{
    /** @var string|null */
    private $code;

    /** @var string */
    private $name;

    /** @var GatewayConfigInterface */
    private $config;

    public function withCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

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
        $method->setCode($this->code);
        $method->setName($this->name);
        $method->setGatewayConfig($this->config);

        return $method;
    }
}
