<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Admin;

use Pledg\SyliusPaymentPlugin\Refund\PledgTransferRefundService;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Refund controller aligned with Odoo payment_pledg wizard:
 *   - Mode 'total':    refund remaining balance
 *   - Mode 'custom':   refund a free amount
 *   - Mode 'products': refund selected order items (qty × unit_price)
 */
final class PledgRefundAction
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private PledgTransferRefundService $refundService,
        private RouterInterface $router,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(Request $request, int $orderId, int $id): Response
    {
        $redirect = new RedirectResponse(
            $this->router->generate('sylius_admin_order_show', ['id' => $orderId])
        );

        if (!$request->isMethod('POST')) {
            return $redirect;
        }

        $order = $this->orderRepository->find($orderId);
        if (null === $order) {
            throw new NotFoundHttpException('Commande introuvable.');
        }

        $payment = $this->paymentRepository->findOneByOrderId($id, $orderId);
        if (!$payment instanceof PaymentInterface) {
            throw new NotFoundHttpException('Paiement introuvable.');
        }

        $token = (string) $request->request->get('_csrf_token', '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('pledge_transfer_refund_' . $id, $token))) {
            $request->getSession()->getFlashBag()->add('error', 'Jeton CSRF invalide.');

            return $redirect;
        }

        if (!$this->refundService->supports($payment)) {
            $request->getSession()->getFlashBag()->add('error', 'Ce paiement n\'est pas un paiement Pledg.');

            return $redirect;
        }

        $remaining = $this->refundService->getRemainingCents($payment);
        if ($remaining <= 0) {
            $request->getSession()->getFlashBag()->add('error', 'Le montant restant est de 0 €. Aucun remboursement possible.');

            return $redirect;
        }

        $mode = (string) $request->request->get('refund_mode', 'custom');
        if (!\in_array($mode, ['total', 'custom', 'products'], true)) {
            $mode = 'custom';
        }

        $amountCents = 0;
        $detailsHtml = null;

        switch ($mode) {
            case 'total':
                $amountCents = $remaining;
                break;

            case 'custom':
                $euros = str_replace(',', '.', (string) $request->request->get('amount_euros', '0'));
                $amountCents = (int) round((float) $euros * 100);
                if ($amountCents <= 0) {
                    $request->getSession()->getFlashBag()->add('error', 'Montant invalide.');

                    return $redirect;
                }
                if ($amountCents > $remaining) {
                    $request->getSession()->getFlashBag()->add(
                        'error',
                        sprintf(
                            'Le montant dépasse le reste à rembourser (%s €).',
                            number_format($remaining / 100, 2, ',', ' '),
                        ),
                    );

                    return $redirect;
                }
                break;

            case 'products':
                $result = $this->computeProductRefund($request, $payment, $remaining);
                if (\is_string($result)) {
                    $request->getSession()->getFlashBag()->add('error', $result);

                    return $redirect;
                }
                [$amountCents, $detailsHtml] = $result;
                break;
        }

        if ($amountCents <= 0) {
            $request->getSession()->getFlashBag()->add('error', 'Montant invalide.');

            return $redirect;
        }

        $explicitPurchaseUid = trim((string) $request->request->get('purchase_uid', ''));
        if ($explicitPurchaseUid === '') {
            $explicitPurchaseUid = null;
        }

        try {
            $this->refundService->refundTransfer($payment, $amountCents, $mode, $detailsHtml, $explicitPurchaseUid);
            $eurosFormatted = number_format($amountCents / 100, 2, ',', ' ');
            $modeLabels = ['total' => 'Total', 'custom' => 'Montant libre', 'products' => 'Sélection produits'];
            $request->getSession()->getFlashBag()->add(
                'success',
                sprintf(
                    'Remboursement Sofinco effectué — %s € (%s).',
                    $eurosFormatted,
                    $modeLabels[$mode] ?? $mode,
                ),
            );
        } catch (\Throwable $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }

        return $redirect;
    }

    /**
     * @return array{0: int, 1: string}|string Error message or [amountCents, detailsHtml]
     */
    private function computeProductRefund(Request $request, PaymentInterface $payment, int $remaining): array|string
    {
        $itemQtys = $request->request->all('refund_items');
        if (!\is_array($itemQtys) || [] === $itemQtys) {
            return 'Sélectionnez au moins un produit.';
        }

        $orderItems = $this->refundService->getOrderItems($payment);
        $orderItemsById = [];
        foreach ($orderItems as $oi) {
            $orderItemsById[$oi['id']] = $oi;
        }

        $totalCents = 0;
        $lines = [];

        foreach ($itemQtys as $itemId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            $oi = $orderItemsById[(int) $itemId] ?? null;
            if (null === $oi) {
                continue;
            }

            if ($qty > $oi['qty']) {
                return sprintf('Quantité trop élevée pour « %s » (max %d).', $oi['name'], $oi['qty']);
            }

            $lineCents = $oi['unit_price_cents'] * $qty;
            $totalCents += $lineCents;
            $lines[] = sprintf('%s &times; %d', htmlspecialchars($oi['name'], \ENT_QUOTES), $qty);
        }

        if ($totalCents <= 0 || [] === $lines) {
            return 'Sélectionnez au moins un produit avec une quantité > 0.';
        }

        if ($totalCents > $remaining) {
            return sprintf(
                'Le total des produits sélectionnés (%s €) dépasse le reste à rembourser (%s €).',
                number_format($totalCents / 100, 2, ',', ' '),
                number_format($remaining / 100, 2, ',', ' '),
            );
        }

        $detailsHtml = '<ul>' . implode('', array_map(static fn (string $l) => '<li>' . $l . '</li>', $lines)) . '</ul>';

        return [$totalCents, $detailsHtml];
    }
}
