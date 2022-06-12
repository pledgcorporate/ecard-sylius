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
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactory;
use Sylius\Component\Order\Model\Adjustment;
use Sylius\Component\Resource\Factory\Factory;
use Tests\Pledg\SyliusPaymentPlugin\PaymentSchedule\SimulationBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\GatewayConfigBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentMethodBuilder;

class PaymentFeeProcessorTest extends TestCase
{
    /** @test */
    public function it_will_do_nothing_when_payment_method_is_not_pledg(): void
    {
        $processor = $this->createProcessor();

        $order = $this->createOrderWithFactoryName('other');

        $processor->process($order);

        self::assertEmpty($order->getAdjustments());
    }

    /** @test */
    public function it_will_add_fees_when_payment_method_is_pledg(): void
    {
        $processor = $this->createProcessor(
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

        $order = $this->createOrderWithFactoryName(PledgGatewayFactory::NAME);

        $processor->process($order);

        self::assertCount(1, $order->getAdjustments());
        self::assertSame(10, $order->getAdjustmentsTotal());
    }

    /** @test */
    public function it_will_replace_fees_when_payment_method_is_pledg_and_order_already_has_fees(): void
    {
        $processor = $this->createProcessor(
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

        $order = $this->createOrderWithFactoryName(PledgGatewayFactory::NAME);
        $adjustment = new Adjustment();
        $adjustment->setType(AdjustmentInterface::PAYMENT_FEES_ADJUSTMENT);
        $adjustment->setAmount(200);
        $order->addAdjustment($adjustment);

        $processor->process($order);

        self::assertCount(1, $order->getAdjustments());
        self::assertSame(10, $order->getAdjustmentsTotal());
    }

    private function createOrderWithFactoryName(string $name): OrderInterface
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
                    ->build(),
            ])
            ->build();
    }

    private function createProcessor(SimulationInterface $simulation = null): PaymentFeeProcessorInterface
    {
        if ($simulation === null) {
            $simulation = $this->prophesize(SimulationInterface::class)->reveal();
        }

        return new PaymentFeeProcessor(
            new AdjustmentFactory(new Factory(Adjustment::class)),
            new MerchantProvider(),
            $simulation
        );
    }
}
