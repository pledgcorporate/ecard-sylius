<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
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
            Status::PENDING_CAPTURE => [new Status(Status::PENDING_CAPTURE)]
        ];
    }
}
