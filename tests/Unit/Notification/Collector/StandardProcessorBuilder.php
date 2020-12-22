<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;


use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Pledg\SyliusPaymentPlugin\Notification\Collector\StandardProcessor;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentProviderInterface;
use Prophecy\Argument;
use Prophecy\Prophet;
use SM\Factory\FactoryInterface;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;

class StandardProcessorBuilder
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var PaymentProviderInterface */
    protected $paymentProvider;

    /** @var FactoryInterface */
    private $stateMachineFactory;

    public function __construct()
    {
        $this->validator = (new StandardValidatorBuilder())->build();
        $this->paymentProvider = (new PaymentProviderBuilder())->build();

        $prophet = new Prophet();
        $factory = $prophet->prophesize(FactoryInterface::class);
        $factory
            ->get(Argument::cetera())
            ->willReturn($prophet->prophesize(StateMachineInterface::class)->reveal());
        $this->stateMachineFactory = $factory->reveal();
    }

    public function withValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function withPaymentProvider(PaymentProviderInterface $paymentProvider): self
    {
        $this->paymentProvider = $paymentProvider;

        return $this;
    }

    public function withStateMachine(StateMachineInterface $stateMachine): self
    {
        $prophet = new Prophet();
        $factory = $prophet->prophesize(FactoryInterface::class);
        $factory
            ->get(Argument::cetera())
            ->willReturn($stateMachine);

        $this->stateMachineFactory = $factory->reveal();

        return $this;
    }

    public function build(): ProcessorInterface
    {
        return new StandardProcessor($this->validator, $this->paymentProvider, $this->stateMachineFactory);
    }
}
