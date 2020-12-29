<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;

use Payum\Core\Exception\RequestNotSupportedException;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\GetStatusBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;

class StatusActionTest extends TestCase
{
    /** @test */
    public function it_supports_pledg_status_action(): void
    {
        $request = (new GetStatusBuilder())->build();
        $action = (new StatusActionBuilder())->build();

        self::assertTrue($action->supports($request));
    }

    /** @test */
    public function it_not_supports_other_action(): void
    {
        $request = (new RedirectUrlBuilder())->build();
        $action = (new StatusActionBuilder())->build();

        self::assertFalse($action->supports($request));

        $this->expectException(RequestNotSupportedException::class);

        $action->execute($request);
    }

    /** @test */
    public function it_marks_request_as_captured_is_pledg_result_get_param_exist_with_completed_status(): void
    {
        $request = (new GetStatusBuilder())->build();
        $action = (new StatusActionBuilder())
            ->withRequestStack($this->buildRequestStackWithStatus(new Status(Status::COMPLETED)))
            ->build();

        $action->execute($request);

        self::assertTrue($request->isCaptured());
    }

    private function buildRequestStackWithStatus(Status $status): RequestStack
    {
        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([
            'pledg_result' => json_encode([
                'transaction' => [
                    'status' => (string) $status,
                ],
            ], \JSON_THROW_ON_ERROR),
        ]);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request->reveal());

        return $requestStack->reveal();
    }
}
