<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Processor;

use Pledg\SyliusPaymentPlugin\Model\AdjustmentInterface;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Webmozart\Assert\Assert;

class PaymentFeeProcessor implements PaymentFeeProcessorInterface
{
    /** @var AdjustmentFactoryInterface */
    private $adjustmentFactory;

    /** @var MerchantProviderInterface */
    private $merchantProvider;

    /** @var OrderProcessorInterface */
    private $orderProcessor;

    /** @var SimulationInterface */
    private $simulation;

    public function __construct(
        AdjustmentFactoryInterface $adjustmentFactory,
        MerchantProviderInterface $merchantProvider,
        OrderProcessorInterface $orderProcessor,
        SimulationInterface $simulation
    ) {
        $this->adjustmentFactory = $adjustmentFactory;
        $this->merchantProvider = $merchantProvider;
        $this->orderProcessor = $orderProcessor;
        $this->simulation = $simulation;
    }

    public function process(OrderInterface $order): void
    {
        Assert::isInstanceOf($order, \Sylius\Component\Core\Model\OrderInterface::class);

        /** @var PaymentInterface $payment */
        $payment = $order->getPayments()->first();

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        if (!$order->getAdjustments(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT)->isEmpty()) {
            $order->removeAdjustments(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);
        }

        if ($paymentMethod->getGatewayConfig() === null
            || $paymentMethod->getGatewayConfig()->getFactoryName() !== PledgGatewayFactory::NAME) {
            $this->orderProcessor->process($order);
            return;
        }

        $paymentSchedule = $this->simulation->simulate(
            $this->merchantProvider->findByMethod($paymentMethod),
            $order->getTotal(),
            new \DateTimeImmutable()
        );

        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->adjustmentFactory->createNew();
        $adjustment->setType(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);
        $adjustment->setAmount($paymentSchedule->getFees());
        $adjustment->setNeutral(false);

        $order->addAdjustment($adjustment);

        $this->orderProcessor->process($order);
    }
}
