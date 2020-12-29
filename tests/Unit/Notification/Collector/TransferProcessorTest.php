<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;
use Pledg\SyliusPaymentPlugin\Notification\Collector\InvalidSignatureException;
use Pledg\SyliusPaymentPlugin\Notification\Collector\NotSupportedException;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\TransferContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class TransferProcessorTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_it_not_supports_the_content(): void
    {
        $processor = (new TransferProcessorBuilder())->build();

        $this->expectException(NotSupportedException::class);

        $processor->process([]);
    }

    /** @test */
    public function it_throws_exception_when_payment_reference_does_not_exist_in_body_content(): void
    {
        $jsonContent = '{"signature":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImp0aSI6ImUwMTg4MjFjLTVkNjUtNDZjNS1hMDAzLWJkNjY1ZDVjZjg2ZiIsImlhdCI6MTYwOTIzMzYxNCwiZXhwIjoxNjA5MjM3MjE0fQ.gGle2IrMhwha0DLZLjktXhEW76AmF06jzyLwtE7ZaU"}';
        $content = json_decode($jsonContent, true, 512, \JSON_THROW_ON_ERROR);
        $processor = (new TransferProcessorBuilder())->build();

        $this->expectException(\InvalidArgumentException::class);

        $processor->process($content);
    }

    /** @test */
    public function it_throws_exception_when_payment_reference_does_not_exist_in_database(): void
    {
        $content = (new TransferContentBuilder())->withValidContent()->build();
        $processor = (new TransferProcessorBuilder())->build();

        $this->expectException(EntityNotFoundException::class);

        $processor->process($content);
    }

    /** @test */
    public function it_throws_exception_when_the_signature_is_invalid(): void
    {
        $content = (new TransferContentBuilder())->withInvalidSignature()->build();
        $processor = $this->buildWithInvalidSignature($content);

        $this->expectException(InvalidSignatureException::class);

        $processor->process($content);
    }

    /** @test */
    public function it_should_update_payment_details_if_content_is_valid(): void
    {
        $content = (new TransferContentBuilder())->withValidContent()->build();
        $body = (new HS256Handler())->decode($content['signature']);
        $payment = $this->buildValidPayment($body);
        $processor = $this->buildWithValidContent($payment);
        $details = $payment->getDetails();

        $processor->process($content);

        self::assertEmpty($details);
        self::assertSame(['notification_content' => $body], $payment->getDetails());
    }

    private function buildValidPayment(array $body): PaymentInterface
    {
        return (new PaymentBuilder())
            ->withId(Reference::fromString($body['reference'])->getPaymentId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->withSecret('secret')
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

        $processorBuilder = (new TransferProcessorBuilder())
            ->withPaymentProvider($paymentProvider)
            ->withValidator(
                (new TransferValidatorBuilder())
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
        $body = (new HS256Handler())->decode($content['signature']);
        $payment = (new PaymentBuilder())
            ->withId(Reference::fromString($body['reference'])->getPaymentId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->build()
            )
            ->build();

        $repository = new InMemoryPaymentRepository();
        $repository->add($payment);

        return (new TransferProcessorBuilder())
            ->withValidator(
                (new TransferValidatorBuilder())
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
