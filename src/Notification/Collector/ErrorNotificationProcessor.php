<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Psr\Log\LoggerInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;

class ErrorNotificationProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private PaymentProviderInterface $paymentProvider,
        private HandlerInterface $handler,
        private StateMachineInterface $stateMachine,
        private LoggerInterface $logger,
    ) {
    }

    public function process(array $content): void
    {
        $content = $this->resolveContent($content);

        if (false === $this->validator->supports($content)) {
            throw NotSupportedException::fromContent($content);
        }

        if (false === $this->validator->validate($content)) {
            throw new \RuntimeException('Pledg error notification: invalid content (missing reference)');
        }

        $this->logger->info('Pledg error IPN: processing', [
            'reference' => $content['reference'] ?? null,
            'state' => $content['state'] ?? null,
            'acceptance_state' => $content['acceptance_state'] ?? null,
            'uid' => $content['uid'] ?? null,
        ]);

        $payment = $this->paymentProvider->getByReference($content['reference']);

        $this->updatePaymentDetails($payment, $content);
        $this->updatePaymentState($payment);

        $this->logger->info('Pledg error IPN: payment marked as failed', [
            'reference' => $content['reference'],
        ]);
    }

    private function resolveContent(array $content): array
    {
        if (1 === \count($content) && isset($content['signature'])) {
            try {
                return $this->handler->decode($content['signature']);
            } catch (\Throwable $e) {
                $this->logger->warning('Pledg error IPN: JWT decode failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $content;
    }

    private function updatePaymentDetails(PaymentInterface $payment, array $content): void
    {
        $details = $payment->getDetails();
        $details['error_notification_content'] = $content;
        $details['pledg_error_state'] = $content['state'] ?? '';
        $details['pledg_acceptance_state'] = $content['acceptance_state'] ?? '';
        $details['pledg_rejection_type'] = $content['acceptance_rejection_type'] ?? '';
        $payment->setDetails($details);
    }

    private function updatePaymentState(PaymentInterface $payment): void
    {
        $graph = PaymentTransitions::GRAPH;
        $toState = (new Status(Status::FAILED))->convertToPaymentState();
        $transition = $this->stateMachine->getTransitionToState($payment, $graph, $toState);
        if (null !== $transition) {
            $this->stateMachine->apply($payment, $graph, $transition);
        }
    }
}
