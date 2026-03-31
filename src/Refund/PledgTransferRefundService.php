<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Refund;

use Doctrine\ORM\EntityManagerInterface;
use Pledg\SyliusPaymentPlugin\Api\PledgBackofficeClient;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\PledgUrl;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Abstraction\StateMachine\StateMachineInterface;

/**
 * Refund service aligned with the Odoo payment_pledg module:
 *   - Three modes: total / custom / products
 *   - Cumulative refund tracking in payment.details['pledg_refunds']
 *   - Remaining amount accounting
 *   - Payment state transition to 'refunded' when fully refunded
 */
final class PledgTransferRefundService
{
    public function __construct(
        private PledgBackofficeClient $backofficeClient,
        private EntityManagerInterface $entityManager,
        private StateMachineInterface $stateMachine,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(PaymentInterface $payment): bool
    {
        $method = $payment->getMethod();
        $gc = $method?->getGatewayConfig();

        return null !== $gc && $gc->getFactoryName() === PledgGatewayFactory::NAME;
    }

    public function getPurchaseUid(PaymentInterface $payment): ?string
    {
        $details = $payment->getDetails();
        $notification = $details['notification_content'] ?? null;
        if (!\is_array($notification)) {
            return null;
        }

        $uid = $notification['purchase_uid'] ?? $notification['id'] ?? null;

        return \is_string($uid) && $uid !== '' ? $uid : null;
    }

    /**
     * Total already refunded (in cents) from local history.
     */
    public function getRefundedTotalCents(PaymentInterface $payment): int
    {
        $refunds = $payment->getDetails()['pledg_refunds'] ?? [];
        if (!\is_array($refunds)) {
            return 0;
        }

        $total = 0;
        foreach ($refunds as $r) {
            $total += (int) ($r['amount_cents'] ?? 0);
        }

        return $total;
    }

    /**
     * Remaining refundable amount (in cents).
     */
    public function getRemainingCents(PaymentInterface $payment): int
    {
        return max(0, (int) $payment->getAmount() - $this->getRefundedTotalCents($payment));
    }

    /**
     * @return array<int, array{date: string, amount_cents: int, amount_eur: string, mode: string, mode_label: string, details: string|null}>
     */
    public function getRefundHistory(PaymentInterface $payment): array
    {
        $refunds = $payment->getDetails()['pledg_refunds'] ?? [];

        return \is_array($refunds) ? $refunds : [];
    }

    /**
     * Execute a refund via the Pledg API and persist the record.
     *
     * @param string      $mode               One of 'total', 'custom', 'products'
     * @param string|null $details             Optional HTML detail (product list)
     * @param string|null $explicitPurchaseUid If provided, overrides auto-detection from payment details
     *
     * @return array<mixed> API response
     */
    public function refundTransfer(
        PaymentInterface $payment,
        int $amountCents,
        string $mode = 'custom',
        ?string $details = null,
        ?string $explicitPurchaseUid = null,
    ): array {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        $remaining = $this->getRemainingCents($payment);
        if ($amountCents > $remaining) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le montant (%s €) dépasse le reste à rembourser (%s €).',
                    number_format($amountCents / 100, 2, ',', ' '),
                    number_format($remaining / 100, 2, ',', ' '),
                )
            );
        }

        $purchaseUid = $explicitPurchaseUid ?? $this->getPurchaseUid($payment);
        if (null === $purchaseUid || '' === trim($purchaseUid)) {
            throw new \RuntimeException(
                'Identifiant d\'achat Pledg (purchase_uid) introuvable. '
                . 'Saisissez-le manuellement depuis le dashboard Pledg (ex: pur_xxx).'
            );
        }
        $purchaseUid = trim($purchaseUid);

        $method = $payment->getMethod();
        if (null === $method) {
            throw new \RuntimeException('Moyen de paiement introuvable.');
        }

        $config = $method->getGatewayConfig()?->getConfig() ?? [];
        $apiKey = trim((string) ($config[PledgGatewayFactory::API_KEY] ?? ''));
        $apiSecret = trim((string) ($config[PledgGatewayFactory::API_SECRET] ?? ''));
        if ($apiKey === '' || $apiSecret === '') {
            $apiKey = (string) ($config[PledgGatewayFactory::IDENTIFIER] ?? '');
            $apiSecret = (string) ($config[PledgGatewayFactory::SECRET] ?? '');
        }
        if ($apiKey === '' || $apiSecret === '') {
            throw new \RuntimeException('Identifiant / secret du gateway Pledg manquants.');
        }

        $backUrl = null;
        if (\array_key_exists(PledgGatewayFactory::SANDBOX, $config)) {
            $backUrl = PledgUrl::backUrl(!empty($config[PledgGatewayFactory::SANDBOX]));
        }

        $token = $this->backofficeClient->getAccessToken($apiKey, $apiSecret, $backUrl);
        $apiResponse = $this->backofficeClient->creditByTransfer($purchaseUid, $amountCents, $token, $backUrl);

        $modeLabels = [
            'total' => 'Total',
            'custom' => 'Montant libre',
            'products' => 'Sélection produits',
        ];

        $refundRecord = [
            'date' => (new \DateTimeImmutable())->format('d/m/Y H:i'),
            'amount_cents' => $amountCents,
            'amount_eur' => number_format($amountCents / 100, 2, ',', ''),
            'mode' => $mode,
            'mode_label' => $modeLabels[$mode] ?? $mode,
            'details' => $details,
        ];

        $paymentDetails = $payment->getDetails();
        $refunds = $paymentDetails['pledg_refunds'] ?? [];
        if (!\is_array($refunds)) {
            $refunds = [];
        }
        $refunds[] = $refundRecord;
        $paymentDetails['pledg_refunds'] = $refunds;
        $payment->setDetails($paymentDetails);

        $newRemaining = (int) $payment->getAmount() - $this->sumRefunds($refunds);
        if ($newRemaining <= 0) {
            try {
                $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_REFUND);
            } catch (\Throwable $e) {
                $this->logger->warning('Could not transition payment to refunded state', [
                    'payment_id' => $payment->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('Pledg refund OK', [
            'payment_id' => $payment->getId(),
            'purchase_uid' => $purchaseUid,
            'amount_cents' => $amountCents,
            'mode' => $mode,
            'remaining_cents' => max(0, $newRemaining),
        ]);

        return $apiResponse;
    }

    /**
     * Build order items data for the product selection mode.
     *
     * @return array<int, array{id: int, name: string, qty: int, unit_price_cents: int, unit_price_eur: string, total_cents: int}>
     */
    public function getOrderItems(PaymentInterface $payment): array
    {
        $order = $payment->getOrder();
        if (!$order instanceof OrderInterface) {
            return [];
        }

        $items = [];
        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }
            $unitPrice = $item->getUnitPrice();
            $qty = $item->getQuantity();
            $items[] = [
                'id' => (int) $item->getId(),
                'name' => (string) $item->getProductName(),
                'qty' => $qty,
                'unit_price_cents' => $unitPrice,
                'unit_price_eur' => number_format($unitPrice / 100, 2, ',', ''),
                'total_cents' => $unitPrice * $qty,
            ];
        }

        return $items;
    }

    private function sumRefunds(array $refunds): int
    {
        $total = 0;
        foreach ($refunds as $r) {
            $total += (int) ($r['amount_cents'] ?? 0);
        }

        return $total;
    }
}
