<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Shop;

use Pledg\SyliusPaymentPlugin\Calculator\TotalWithoutFeesInterface;
use Sylius\Bundle\MoneyBundle\Formatter\MoneyFormatterInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class RecalculateTotalAction
{
    /** @var TotalWithoutFeesInterface */
    private $totalWithoutFeesCalculator;

    /** @var CartContextInterface */
    private $cartContext;

    /** @var MoneyFormatterInterface */
    private $formatter;

    public function __construct(
        TotalWithoutFeesInterface $totalWithoutFeesCalculator,
        CartContextInterface $cartContext,
        MoneyFormatterInterface $formatter
    ) {
        $this->totalWithoutFeesCalculator = $totalWithoutFeesCalculator;
        $this->cartContext = $cartContext;
        $this->formatter = $formatter;
    }

    public function __invoke(int $fees): JsonResponse
    {
        /** @var OrderInterface $cart */
        $cart = $this->cartContext->getCart();
        /** @var string $currencyCode */
        $currencyCode = $cart->getCurrencyCode();
        $totalWithoutFees = $this->totalWithoutFeesCalculator->calculate($cart);

        return new JsonResponse([
            'fees' => $this->formatter->format($fees, $currencyCode, $cart->getLocaleCode()),
            'total' => $this->formatter->format($totalWithoutFees + $fees, $currencyCode, $cart->getLocaleCode()),
        ]);
    }
}
