<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\EventSubscriber;

use Pledg\SyliusPaymentPlugin\Processor\PaymentFeeProcessorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

final class CheckoutSelectPaymentFeeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PaymentFeeProcessorInterface $paymentFeeProcessor,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            \sprintf('workflow.%s.completed.%s', 'sylius_order_checkout', 'select_payment') => 'onSelectPaymentCompleted',
        ];
    }

    public function onSelectPaymentCompleted(Event $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $this->paymentFeeProcessor->process($order);
    }
}
