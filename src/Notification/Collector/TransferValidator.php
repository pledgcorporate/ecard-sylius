<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Webmozart\Assert\Assert;

class TransferValidator implements ValidatorInterface
{
    protected const SIGNATURE_KEY = 'signature';

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var MerchantProviderInterface */
    protected $merchantProvider;

    /** @var HandlerInterface */
    protected $handler;

    public function __construct(
        PaymentProviderInterface $paymentProvider,
        MerchantProviderInterface $merchantProvider,
        HandlerInterface $handler
    ) {
        $this->paymentProvider = $paymentProvider;
        $this->merchantProvider = $merchantProvider;
        $this->handler = $handler;
    }

    public function supports(array $content): bool
    {
        return 1 === count($content) && array_key_exists(self::SIGNATURE_KEY, $content);
    }

    public function validate(array $content): bool
    {
        $body = $this->handler->decode($content[self::SIGNATURE_KEY]);

        Assert::keyExists($body, 'reference');

        $payment = $this->paymentProvider->getByReference($body['reference']);
        $merchant = $this->merchantProvider->findByPayment($payment);

        return $this->handler->verify($content[self::SIGNATURE_KEY], $merchant->getSecret());
    }
}
