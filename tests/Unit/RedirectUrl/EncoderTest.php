<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\RedirectUrl;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;
use Pledg\SyliusPaymentPlugin\RedirectUrl\Encoder;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilder;
use Symfony\Component\Routing\RouterInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;

class EncoderTest extends TestCase
{
    /** @test */
    public function it_encode_correctly_parameters(): void
    {
        $handler = new HS256Handler();
        $encoder = new Encoder($handler);
        $request = (new RedirectUrlBuilder())->withCompleteCaptureRequest()->build();
        $parameters = ParamBuilder::fromRedirectUrlRequest($request, $this->prophesize(RouterInterface::class)->reveal())->build();

        $token = $encoder->encode($parameters, $request->getMerchant()->getSecret());

        self::assertSame(['data' => $parameters], $handler->decode($token));
    }
}
