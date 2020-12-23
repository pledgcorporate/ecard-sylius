<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Webmozart\Assert\Assert;

class StandardProcessor implements ProcessorInterface
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var FactoryInterface */
    private $stateMachineFactory;

    public function __construct(
        ValidatorInterface $validator,
        PaymentProviderInterface $paymentProvider,
        FactoryInterface $stateMachineFactory
    ) {
        $this->validator = $validator;
        $this->paymentProvider = $paymentProvider;
        $this->stateMachineFactory = $stateMachineFactory;
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
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);

        Assert::isInstanceOf($stateMachine, StateMachineInterface::class);

        if (null !== $transition = $stateMachine->getTransitionToState($status->convertToPaymentState())) {
            $stateMachine->apply($transition);
        }
    }
}
