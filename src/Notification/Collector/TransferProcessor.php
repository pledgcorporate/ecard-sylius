<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Webmozart\Assert\Assert;

class TransferProcessor implements ProcessorInterface
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var HandlerInterface */
    protected $handler;

    /** @var FactoryInterface */
    private $stateMachineFactory;

    public function __construct(
        ValidatorInterface $validator,
        PaymentProviderInterface $paymentProvider,
        HandlerInterface $handler,
        FactoryInterface $stateMachineFactory
    ) {
        $this->validator = $validator;
        $this->paymentProvider = $paymentProvider;
        $this->handler = $handler;
        $this->stateMachineFactory = $stateMachineFactory;
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
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);

        Assert::isInstanceOf($stateMachine, StateMachineInterface::class);

        if (null !== $transition = $stateMachine->getTransitionToState($status->convertToPaymentState())) {
            $stateMachine->apply($transition);
        }
    }
}
