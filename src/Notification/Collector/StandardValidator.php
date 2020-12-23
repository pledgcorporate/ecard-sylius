<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;

class StandardValidator implements ValidatorInterface
{
    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var MerchantProviderInterface */
    protected $merchantProvider;

    protected const REQUIRED_KEYS = [
        'created_at',
        'id',
        'additional_data',
        'metadata',
        'status',
        'sandbox',
        'error',
        'reference',
        'signature',
    ];

    protected const SIGNED_KEYS = [
        'created_at',
        'error',
        'id',
        'reference',
        'sandbox',
        'status',
    ];

    public function __construct(
        PaymentProviderInterface $paymentProvider,
        MerchantProviderInterface $merchantProvider
    ) {
        $this->paymentProvider = $paymentProvider;
        $this->merchantProvider = $merchantProvider;
    }

    public function supports(array $content): bool
    {
        return count(self::REQUIRED_KEYS) === count(array_intersect_key(array_flip(self::REQUIRED_KEYS), $content));
    }

    public function validate(array $content): bool
    {
        return $this->validateSignature($content);
    }

    protected function validateSignature(array $content): bool
    {
        $payment = $this->paymentProvider->getByReference($content['reference']);
        $merchant = $this->merchantProvider->findByPayment($payment);

        $signature = $this->createSignature($content, $merchant);

        return $content['signature'] === $signature;
    }

    protected function createSignature(array $content, MerchantInterface $merchant): string
    {
        $parameters = $this->retrieveSignedParameters($content);
        /** @var string $hash */
        $hash = hash('SHA256', implode($merchant->getSecret(), $parameters));

        return strtoupper($hash);
    }

    protected function retrieveSignedParameters(array $content): array
    {
        $parameters = array_intersect_key($content, array_flip(self::SIGNED_KEYS));
        ksort($parameters);

        return array_map(static function (string $key, $value): string {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            return sprintf('%s=%s', $key, $value);
        }, array_keys($parameters), $parameters);
    }
}
