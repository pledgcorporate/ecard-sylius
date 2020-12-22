<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;


use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Notification\Collector\InvalidSignatureException;
use Pledg\SyliusPaymentPlugin\Notification\Collector\NotSupportedException;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachine;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\StandardContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class StandardProcessorTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_it_not_supports_the_content(): void
    {
        $processor = (new StandardProcessorBuilder())->build();

        $this->expectException(NotSupportedException::class);

        $processor->process(['signature' => '']);
    }

    /** @test */
    public function it_throws_exception_when_the_signature_is_invalid(): void
    {
        $content = (new StandardContentBuilder())->build();
        $processor = $this->buildWithInvalidSignature($content);

        $this->expectException(InvalidSignatureException::class);

        $processor->process($content);
    }

    /** @test */
    public function it_should_update_payment_details_if_content_is_valid(): void
    {
        $content = (new StandardContentBuilder())->build();
        $payment = $this->buildValidPayment($content);
        $processor = $this->buildWithValidContent($payment);
        $details = $payment->getDetails();

        $processor->process($content);

        self::assertEmpty($details);
        self::assertSame(['notification_content' => $content], $payment->getDetails());
    }

    /** @test */
    public function it_should_update_payment_state_if_content_is_valid_and_transition_exist(): void
    {
        $content = (new StandardContentBuilder())->build();
        $payment = $this->buildValidPayment($content);
        $stateMachine = $this->prophesize(StateMachineInterface::class);
        $stateMachine
            ->getTransitionToState((new Status($content['status']))->convertToPaymentState())
            ->willReturn(PaymentTransitions::TRANSITION_COMPLETE);

        $stateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE)->shouldBeCalled();

        $processor = $this->buildWithValidContent($payment, $stateMachine->reveal());

        $processor->process($content);
    }

    private function buildValidPayment(array $content): PaymentInterface
    {
        return (new PaymentBuilder())
            ->withId(Reference::fromString($content['reference'])->getId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->withSecret('SECRET')
                    ->build()
            )
            ->build();
    }

    private function buildWithValidContent(PaymentInterface $payment, StateMachineInterface $stateMachine = null): ProcessorInterface
    {
        $repository = new InMemoryPaymentRepository();
        $repository->add($payment);
        $paymentProvider = (new PaymentProviderBuilder())
            ->withRepository($repository)
            ->build();

        $processorBuilder = (new StandardProcessorBuilder())
            ->withPaymentProvider($paymentProvider)
            ->withValidator(
                (new StandardValidatorBuilder())
                    ->withPaymentProvider($paymentProvider)
                    ->build()
            );

        if (null !== $stateMachine) {
            $processorBuilder->withStateMachine($stateMachine);
        }

        return $processorBuilder->build();
    }

    private function buildWithInvalidSignature(array $content): ProcessorInterface
    {
        $payment = (new PaymentBuilder())
            ->withId(Reference::fromString($content['reference'])->getId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->build()
            )
            ->build();

        $repository = new InMemoryPaymentRepository();
        $repository->add($payment);

        return (new StandardProcessorBuilder())
            ->withValidator(
                (new StandardValidatorBuilder())
                    ->withPaymentProvider(
                        (new PaymentProviderBuilder())
                            ->withRepository($repository)
                            ->build()
                    )
                    ->build()
            )
            ->build();
    }
}
