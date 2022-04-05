<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Provider;

use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

class MerchantProvider implements MerchantProviderInterface
{
    public function findByPayment(PaymentInterface $payment): MerchantInterface
    {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        Assert::notNull($paymentMethod->getGatewayConfig());

        $config = $paymentMethod->getGatewayConfig()->getConfig();

        Assert::keyExists($config, PledgGatewayFactory::IDENTIFIER);
        Assert::keyExists($config, PledgGatewayFactory::SECRET);

        return new Merchant($config[PledgGatewayFactory::IDENTIFIER], $config[PledgGatewayFactory::SECRET]);
    }
}
