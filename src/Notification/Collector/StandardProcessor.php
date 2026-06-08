<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;

class StandardProcessor implements ProcessorInterface
{
    public function __construct(
        protected ValidatorInterface $validator,
        protected PaymentProviderInterface $paymentProvider,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function process(array $content): void
    {
        if (false === $this->validator->supports($content)) {
            throw NotSupportedException::fromContent($content);
        }

        if (false === $this->validator->validate($content)) {
            throw InvalidSignatureException::withSignatureAndContent($content['signature'], $content);
        }

        $payment = $this->paymentProvider->getByReference($content['reference']);

        $this->updatePaymentDetails($payment, $content);
        $this->updatePaymentState($payment, new Status($content['status']));
    }

    protected function updatePaymentDetails(PaymentInterface $payment, array $content): void
    {
        $details = $payment->getDetails();
        $details['notification_content'] = $content;
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
