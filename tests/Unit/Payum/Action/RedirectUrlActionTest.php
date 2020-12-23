<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;


use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Action\RedirectUrlAction;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action\RedirectUrlActionBuilder;

class RedirectUrlActionTest extends TestCase
{
    /** @test */
    public function it_supports_pledg_capture_action(): void
    {
        $request = (new RedirectUrlBuilder())->build();
        $action = (new RedirectUrlActionBuilder())->build();

        self::assertTrue($action->supports($request));
    }

    /** @test */
    public function it_not_supports_other_action(): void
    {
        $request = (new CaptureBuilder())->build();
        $action = (new RedirectUrlActionBuilder())->build();

        self::assertFalse($action->supports($request));

        $this->expectException(RequestNotSupportedException::class);

        $action->execute($request);
    }

    /** @test */
    public function it_throw_HttpRedirect_response_with_valid_request(): void
    {
        $request = (new RedirectUrlBuilder())
            ->withCompleteCaptureRequest()
            ->build();
        $action = (new RedirectUrlActionBuilder())->build();

        $this->expectException(HttpRedirect::class);

        $action->execute($request);
    }
}
