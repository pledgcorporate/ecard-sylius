<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\RedirectUrl;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilder;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request\RedirectUrlBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\AddressBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\CustomerBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\OrderItemBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\ShipmentBuilder;

class ParamBuilderTest extends TestCase
{
    /** @test */
    public function it_builds_param_with_valid_RedirectUrl_request(): void
    {
        $redirectUrl = (new RedirectUrlBuilder())->withCompleteCaptureRequest()->build();

        $parameters = ParamBuilder::fromRedirectUrlRequest($redirectUrl, $this->prophesize(RouterInterface::class)->reveal())->build();

        self::assertArrayHasKey('merchantUid', $parameters);
        self::assertArrayHasKey('lang', $parameters);
        self::assertArrayHasKey('title', $parameters);
        self::assertArrayHasKey('reference', $parameters);
        self::assertArrayHasKey('amountCents', $parameters);
        self::assertArrayHasKey('currency', $parameters);
        self::assertArrayHasKey('firstName', $parameters);
        self::assertArrayHasKey('lastName', $parameters);
        self::assertArrayHasKey('email', $parameters);
        self::assertArrayHasKey('phoneNumber', $parameters);
        self::assertArrayHasKey('countryCode', $parameters);
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

    /** @test */
    public function it_builds_param_with_metadata(): void
    {
        $order = (new OrderBuilder())
            ->withItems([
                (new OrderItemBuilder())
                    ->withVariantCode('SKU1')
                    ->withVariantName('product 1')
                    ->withQuantity(2)
                    ->withUnitPrice(100)
                    ->isShippingRequired(true)
                    ->build(),
                (new OrderItemBuilder())
                    ->withVariantCode('SKU2')
                    ->withVariantName('product 2')
                    ->withQuantity(1)
                    ->withUnitPrice(500)
                    ->isShippingRequired(false)
                    ->build(),
            ])
            ->withCustomer(
                (new CustomerBuilder())
                    ->withId(12345)
                    ->withCreatedAt(new \DateTimeImmutable('2022-03-27'))
                    ->build()
            )
            ->withShipments([
                (new ShipmentBuilder())
                    ->withSippingMethodName('UPS')
                    ->build(),
            ])
            ->withBillingAddress((new AddressBuilder())->build())
            ->withShippingAddress((new AddressBuilder())->build())
            ->build();

        $paramBuilder = $this->createWithOrder($order)->build();

        self::assertArrayHasKey('metadata', $paramBuilder);
        self::assertSame([
            'delivery_label' => 'UPS',
            'products' => [
                [
                    'reference' => 'SKU1',
                    'name' => 'product 1',
                    'quantity' => 2,
                    'unit_amount_cents' => 100,
                    'type' => 'physical',
                ],
                [
                    'reference' => 'SKU2',
                    'name' => 'product 2',
                    'quantity' => 1,
                    'unit_amount_cents' => 500,
                    'type' => 'virtual',
                ],
            ],
            'account' => [
                'creation_date' => '2022-03-27',
            ],
            'session' => [
                'customer_id' => 12345,
            ],
            'plugin' => 'sylius1.7-pledg-plugin0.1',
        ], $paramBuilder['metadata']);
    }

    private function createWithOrder(OrderInterface $order): ParamBuilder
    {
        $redirectUrl = (new RedirectUrlBuilder())
            ->withOrder($order)
            ->build();

        return ParamBuilder::fromRedirectUrlRequest($redirectUrl, $this->prophesize(RouterInterface::class)->reveal());
    }
}
