<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\RedirectUrl;


use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\RedirectUrl\Encoder;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilder;
use Symfony\Component\Routing\RouterInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;

class EncoderTest extends TestCase
{
    /** @test */
    public function it_encode_correctly_parameters(): void
    {
        $encoder = new Encoder();
        $request = (new RedirectUrlBuilder())->withCompleteCaptureRequest()->build();
        $parameters = ParamBuilder::fromRedirectUrlRequest($request, $this->prophesize(RouterInterface::class)->reveal())->build();

        $token = $encoder->encode($parameters, $request->getMerchant()->getSecret());

        self::assertSame(['data' => $parameters], $encoder->decode($token));
    }
}
