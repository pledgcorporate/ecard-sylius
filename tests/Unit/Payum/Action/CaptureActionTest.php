<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayInterface;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Action\CaptureAction;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Prophecy\Argument;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\CaptureBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class CaptureActionTest extends TestCase
{
    /** @test */
    public function it_supports_pledg_capture_action(): void
    {
        $request = (new CaptureBuilder())->build();
        $captureAction = new CaptureAction();
        $captureAction->setApi((new MerchantBuilder())->build());

        self::assertTrue($captureAction->supports($request));
    }

    /** @test */
    public function it_not_supports_other_action(): void
    {
        $request = (new RedirectUrlBuilder())->build();
        $captureAction = new CaptureAction();
        $captureAction->setApi((new MerchantBuilder())->build());

        self::assertFalse($captureAction->supports($request));

        $this->expectException(RequestNotSupportedException::class);

        $captureAction->execute($request);
    }

    /** @test */
    public function it_executes_redirect_url_action_with_capture_action(): void
    {
        $gatewayProphecy = $this->prophesize(GatewayInterface::class);
        /** @var GatewayInterface $gateway */
        $gateway = $gatewayProphecy->reveal();

        $request = (new CaptureBuilder())->build();
        $captureAction = new CaptureAction();
        $captureAction->setApi((new MerchantBuilder())->build());
        $captureAction->setGateway($gateway);

        $captureAction->execute($request);

        $gatewayProphecy->execute(Argument::type(RedirectUrl::class))->shouldHaveBeenCalled();
    }
}
