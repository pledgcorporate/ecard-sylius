<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Model\AdjustmentInterface;
use Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Processor\PaymentFeeProcessor;
use Pledg\SyliusPaymentPlugin\Processor\PaymentFeeProcessorInterface;
use Pledg\SyliusPaymentPlugin\Provider\MerchantProvider;
use Prophecy\Argument;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderProcessing\OrderPaymentProcessor;
use Sylius\Component\Core\Payment\Provider\OrderPaymentProviderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactory;
use Sylius\Component\Order\Model\Adjustment;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Resource\Factory\Factory;
use Tests\Pledg\SyliusPaymentPlugin\Unit\PaymentSchedule\SimulationBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\GatewayConfigBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderItemBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentMethodBuilder;

class PaymentFeeProcessorTest extends TestCase
{
    /** @test */
    public function it_will_do_nothing_when_payment_method_is_not_pledg(): void
    {
        $order = $this->createOrderWithFactoryNameAndAmount('other', 100);
        $processor = $this->createProcessor($order);

        $processor->process($order);

        $payment = $order->getPayments()->first();
        self::assertInstanceOf(PaymentInterface::class, $payment);
        self::assertEmpty($order->getAdjustments());
        self::assertSame(100, $order->getTotal());
        self::assertSame(100, $payment->getAmount());
    }

    /** @test */
    public function it_will_add_fees_when_payment_method_is_pledg(): void
    {
        $order = $this->createOrderWithFactoryNameAndAmount(PledgGatewayFactory::NAME, 100);

        $processor = $this->createProcessor(
            $order,
            (new SimulationBuilder())
                ->withSimulation([
                    'INSTALLMENT' => [
                        [
                            'payment_date' => '2022-02-02',
                            'amount_cents' => 100,
                            'fees' => 10,
                        ],
                        [
                            'payment_date' => '2022-03-02',
                            'amount_cents' => 100,
                            'fees' => 0,
                        ],
                    ],
                ])
                ->build()
        );

        $processor->process($order);

        $payment = $order->getPayments()->first();
        self::assertInstanceOf(PaymentInterface::class, $payment);
        self::assertCount(1, $order->getAdjustments());
        self::assertSame(10, $order->getAdjustmentsTotal());
        self::assertSame(110, $order->getTotal());
        self::assertSame(110, $payment->getAmount());
    }

    /** @test */
    public function it_will_replace_fees_when_payment_method_is_pledg_and_order_already_has_fees(): void
    {
        $order = $this->createOrderWithFactoryNameAndAmount(PledgGatewayFactory::NAME, 100);
        $processor = $this->createProcessor(
            $order,
            (new SimulationBuilder())
                ->withSimulation([
                    'INSTALLMENT' => [
                        [
                            'payment_date' => '2022-02-02',
                            'amount_cents' => 100,
                            'fees' => 10,
                        ],
                    ],
                ])
                ->build()
        );
        $oldAdjustment = new Adjustment();
        $oldAdjustment->setType(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);
        $oldAdjustment->setAmount(200);
        $order->addAdjustment($oldAdjustment);

        $processor->process($order);

        $payment = $order->getPayments()->first();
        self::assertInstanceOf(PaymentInterface::class, $payment);
        self::assertCount(1, $order->getAdjustments());
        self::assertSame(10, $order->getAdjustmentsTotal());
        self::assertSame(110, $order->getTotal());
        self::assertSame(110, $payment->getAmount());
    }

    /** @test */
    public function it_will_remove_fees_when_new_payment_does_not_has_fees_and_order_already_has_fees(): void
    {
        $order = $this->createOrderWithFactoryNameAndAmount('other', 100);
        $processor = $this->createProcessor(
            $order,
            (new SimulationBuilder())
                ->withSimulation([])
                ->build()
        );
        $oldAdjustment = new Adjustment();
        $oldAdjustment->setType(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);
        $oldAdjustment->setAmount(200);
        $order->addAdjustment($oldAdjustment);

        $processor->process($order);

        $payment = $order->getPayments()->first();
        self::assertInstanceOf(PaymentInterface::class, $payment);
        self::assertCount(0, $order->getAdjustments());
        self::assertSame(0, $order->getAdjustmentsTotal());
        self::assertSame(100, $order->getTotal());
        self::assertSame(100, $payment->getAmount());
    }

    private function createOrderWithFactoryNameAndAmount(string $name, int $amount): OrderInterface
    {
        return (new OrderBuilder())
            ->withPayments([
                (new PaymentBuilder())
                    ->withMethod(
                        (new PaymentMethodBuilder())
                            ->withConfig(
                                (new GatewayConfigBuilder())
                                    ->withFactoryName($name)
                                    ->withConfig(PledgGatewayFactory::IDENTIFIER, '')
                                    ->withConfig(PledgGatewayFactory::SECRET, '')
                                    ->build()
                            )
                            ->build()
                    )
                    ->withAmountInCents($amount)
                    ->build(),
            ])
            ->withItems(
                [
                    (new OrderItemBuilder())
                        ->withUnitPrice($amount)
                        ->withQuantity(1)
                        ->isShippingRequired(false)
                        ->build(),
                ]
            )
            ->build();
    }

    private function createProcessor(OrderInterface $order, SimulationInterface $simulation = null): PaymentFeeProcessorInterface
    {
        if ($simulation === null) {
            $simulation = $this->prophesize(SimulationInterface::class)->reveal();
        }

        return new PaymentFeeProcessor(
            new AdjustmentFactory(new Factory(Adjustment::class)),
            new MerchantProvider(),
            $this->buildOrderProcessor($order),
            $simulation
        );
    }

    private function buildOrderProcessor(OrderInterface $order): OrderProcessorInterface
    {
        $paymentProvider = $this->prophesize(OrderPaymentProviderInterface::class);
        $paymentProvider->provideOrderPayment(Argument::cetera())->willReturn($order->getPayments()->first());

        return new OrderPaymentProcessor($paymentProvider->reveal());
    }
}
