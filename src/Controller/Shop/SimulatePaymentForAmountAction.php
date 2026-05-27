<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Shop;

use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Provider\PledgGatewayConfigReader;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Simulation pour une page produit / widget : montant explicite (centimes), sans panier.
 */
final class SimulatePaymentForAmountAction
{
    public function __construct(
        private Environment $twig,
        private SimulationInterface $simulation,
        private MerchantProviderInterface $merchantProvider,
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private PledgGatewayConfigReader $pledgGatewayConfigReader,
    ) {
    }

    public function __invoke(string $code, int $amountCents): Response
    {
        if ($amountCents <= 0) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        try {
            $method = $this->paymentMethodProvider->findOneByCode($code);
        } catch (\Throwable) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $merchant = $this->merchantProvider->findByMethod($method);

        $scheduleUid = $this->pledgGatewayConfigReader->getScheduleMerchantUidForPaymentMethodCode($code);
        $gwConfig = $this->pledgGatewayConfigReader->getConfigForPaymentMethodCode($code);
        $backUrl = $this->pledgGatewayConfigReader->getBackUrl($gwConfig);

        return new Response($this->twig->render(
            '@PledgSyliusPaymentPlugin/Checkout/SelectPayment/simulation.html.twig',
            [
                'simulation' => $this->simulation->simulate($merchant, $amountCents, new \DateTimeImmutable(), $scheduleUid, $backUrl),
            ]
        ));
    }
}
