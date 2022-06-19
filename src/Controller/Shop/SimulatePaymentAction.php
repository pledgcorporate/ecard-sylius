<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Shop;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class SimulatePaymentAction
{
    /** @var Environment */
    private $twig;

    /** @var CartContextInterface */
    private $cartContext;

    /** @var SimulationInterface */
    private $simulation;

    /** @var MerchantProviderInterface */
    private $merchantProvider;

    /** @var PaymentMethodProviderInterface */
    private $paymentMethodProvider;

    public function __construct(
        Environment $twig,
        CartContextInterface $cartContext,
        SimulationInterface $simulation,
        MerchantProviderInterface $merchantProvider,
        PaymentMethodProviderInterface $paymentMethodProvider
    ) {
        $this->twig = $twig;
        $this->cartContext = $cartContext;
        $this->simulation = $simulation;
        $this->merchantProvider = $merchantProvider;
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    public function __invoke(string $code): Response
    {
        $amount = $this->cartContext->getCart()->getTotal();
        $merchant = $this->merchantProvider->findByMethod(
            $this->paymentMethodProvider->findOneByCode($code)
        );

        return  new Response($this->twig->render(
            '@PledgSyliusPaymentPlugin/Checkout/SelectPayment/simulation.html.twig',
            [
                'simulation' => $this->simulation->simulate($merchant, $amount, new \DateTimeImmutable()),
            ]
        ));
    }
}
