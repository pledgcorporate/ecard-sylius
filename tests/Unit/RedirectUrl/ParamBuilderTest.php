<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\RedirectUrl;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilder;
use Symfony\Component\Routing\RouterInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;

class ParamBuilderTest extends TestCase
{
    /** @test */
    public function it_builds_param_with_valid_RedirectUrl_request(): void
    {
        $redirectUrl = (new RedirectUrlBuilder())->withCompleteCaptureRequest()->build();

        $parameters = ParamBuilder::fromRedirectUrlRequest($redirectUrl,  $this->prophesize(RouterInterface::class)->reveal())->build();

        self::assertArrayHasKey('merchantUid', $parameters);
        self::assertArrayHasKey('title', $parameters);
        self::assertArrayHasKey('reference', $parameters);
        self::assertArrayHasKey('amountCents', $parameters);
        self::assertArrayHasKey('currency', $parameters);
        self::assertArrayHasKey('firstName', $parameters);
        self::assertArrayHasKey('lastName', $parameters);
        self::assertArrayHasKey('email', $parameters);
        self::assertArrayHasKey('phoneNumber', $parameters);
        self::assertArrayHasKey('redirectUrl', $parameters);
        self::assertArrayHasKey('cancelUrl', $parameters);
        self::assertArrayHasKey('paymentNotificationUrl', $parameters);
        self::assertArrayHasKey('address', $parameters);
        self::assertArrayHasKey('street', $parameters['address']);
        self::assertArrayHasKey('city', $parameters['address']);
        self::assertArrayHasKey('zipcode', $parameters['address']);
        self::assertArrayHasKey('country', $parameters['address']);
        self::assertArrayHasKey('shippingAddress', $parameters);
        self::assertArrayHasKey('street', $parameters['shippingAddress']);
        self::assertArrayHasKey('city', $parameters['shippingAddress']);
        self::assertArrayHasKey('zipcode', $parameters['shippingAddress']);
        self::assertArrayHasKey('country', $parameters['shippingAddress']);
    }
}
