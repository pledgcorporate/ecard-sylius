<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Twig\Extension;

use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PledgExtension extends AbstractExtension
{
    /** @var PaymentMethodProviderInterface */
    private $paymentMethodProvider;

    /** @var string[]|null */
    private $pledgMethodCodes;

    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_pledg_method', [$this, 'isPledgMethod']),
        ];
    }

    public function isPledgMethod(string $code): bool
    {
        return in_array($code, $this->getPledgMethodCodes(), true);
    }

    private function getPledgMethodCodes(): array
    {
        if (null === $this->pledgMethodCodes) {
            $this->pledgMethodCodes = array_map(
                static function (PaymentMethodInterface $method): string {
                    return (string) $method->getCode();
                },
                $this->paymentMethodProvider->getPledgMethods()
            );
        }

        return $this->pledgMethodCodes;
    }
}
