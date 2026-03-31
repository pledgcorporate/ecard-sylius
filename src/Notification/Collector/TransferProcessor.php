<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;

class TransferProcessor implements ProcessorInterface
{
    public function __construct(
        protected ValidatorInterface $validator,
        protected PaymentProviderInterface $paymentProvider,
        protected HandlerInterface $handler,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function process(array $content): void
    {
        if (false === $this->validator->supports($content)) {
            throw NotSupportedException::fromContent($content);
        }

        if (false === $this->validator->validate($content)) {
            throw InvalidSignatureException::withSignature($content['signature']);
        }

        $body = $this->handler->decode($content['signature']);
        $payment = $this->paymentProvider->getByReference($body['reference']);

        $this->updatePaymentDetails($payment, $body);

        if (isset($body['transfer_order_item_uid'])) {
            $this->updatePaymentState($payment, new Status(Status::COMPLETED));
        }
    }

    protected function updatePaymentDetails(PaymentInterface $payment, array $body): void
    {
        $details = $payment->getDetails();
        $details['notification_content'] = $body;
        $payment->setDetails($details);
    }

    protected function updatePaymentState(PaymentInterface $payment, Status $status): void
    {
        $graph = PaymentTransitions::GRAPH;
        $toState = $status->convertToPaymentState();
        if (null !== $transition = $this->stateMachine->getTransitionToState($payment, $graph, $toState)) {
            $this->stateMachine->apply($payment, $graph, $transition);
        }
    }
}
