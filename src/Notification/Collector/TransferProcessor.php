<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Pledg\SyliusPaymentPlugin\JWT\HandlerInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;

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
    }

    protected function updatePaymentDetails(PaymentInterface $payment, array $body): void
    {
        $details = $payment->getDetails();
        $details['notification_content'] = $body;
        $payment->setDetails($details);
    }
}
