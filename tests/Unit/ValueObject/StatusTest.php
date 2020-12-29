<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Sylius\Component\Core\Model\PaymentInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\GetStatusBuilder;

class StatusTest extends TestCase
{
    /** @test */
    public function it_initializes_with_valid_value(): void
    {
        $status = new Status(Status::PENDING);
        self::assertSame(Status::PENDING, (string) $status);
    }

    /** @test */
    public function it_throw_exception_with_invalid_value(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new Status('invalid');
    }

    /** @test */
    public function it_marks_request_as_captured_with_completed_status(): void
    {
        $request = (new GetStatusBuilder())->build();
        (new Status(Status::COMPLETED))->markRequest($request);

        self::assertTrue($request->isCaptured());
    }

    /**
     * @test
     * @dataProvider pendingStatus
     */
    public function it_marks_request_as_pending_(Status $status): void
    {
        $request = (new GetStatusBuilder())->build();

        $status->markRequest($request);

        self::assertTrue($request->isPending());
    }

    public function pendingStatus(): array
    {
        return [
            Status::PENDING => [new Status(Status::PENDING)],
            Status::PENDING_CAPTURE => [new Status(Status::PENDING_CAPTURE)],
        ];
    }

    /**
     * @test
     * @dataProvider convertStatus
     */
    public function it_convert_status_to_payment_state(Status $status, string $state): void
    {
        self::assertSame($status->convertToPaymentState(), $state);
    }

    public function convertStatus(): array
    {
        return [
            Status::PENDING => [new Status(Status::PENDING), PaymentInterface::STATE_PROCESSING],
            Status::PENDING_CAPTURE => [new Status(Status::PENDING_CAPTURE), PaymentInterface::STATE_PROCESSING],
            Status::IN_REVIEW => [new Status(Status::IN_REVIEW), PaymentInterface::STATE_PROCESSING],
            Status::AUTHORIZED => [new Status(Status::AUTHORIZED), PaymentInterface::STATE_AUTHORIZED],
            Status::COMPLETED => [new Status(Status::COMPLETED), PaymentInterface::STATE_COMPLETED],
            Status::FAILED => [new Status(Status::FAILED), PaymentInterface::STATE_FAILED],
            Status::BLOCKED => [new Status(Status::BLOCKED), PaymentInterface::STATE_FAILED],
            Status::REFUNDED => [new Status(Status::REFUNDED), PaymentInterface::STATE_REFUNDED],
            Status::CHARGEBACK_INITIATED => [new Status(Status::CHARGEBACK_INITIATED), PaymentInterface::STATE_UNKNOWN],
            Status::FRAUD_NOTIFICATION => [new Status(Status::FRAUD_NOTIFICATION), PaymentInterface::STATE_UNKNOWN],
            Status::RESERVED => [new Status(Status::RESERVED), PaymentInterface::STATE_UNKNOWN],
            Status::SOLVED => [new Status(Status::SOLVED), PaymentInterface::STATE_UNKNOWN],
            Status::VOIDED => [new Status(Status::VOIDED), PaymentInterface::STATE_UNKNOWN],
            Status::RETRIEVAL_REQUEST => [new Status(Status::RETRIEVAL_REQUEST), PaymentInterface::STATE_UNKNOWN],
            Status::WAITING => [new Status(Status::WAITING), PaymentInterface::STATE_UNKNOWN],
        ];
    }
}
