<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\StandardContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class TransferValidatorTest extends TestCase
{
    /** @test */
    public function it_supports_only_signature_key(): void
    {
        $validator = (new TransferValidatorBuilder())->build();

        self::assertTrue($validator->supports(['signature' => '']));
        self::assertFalse($validator->supports((new StandardContentBuilder())->build()));
    }

    /** @test */
    public function it_validate_valid_signature(): void
    {
        $jsonContent = '{"signature":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyZWZlcmVuY2UiOiJQTEVER18xMjZfMTU4IiwiY3JlYXRlZCI6IjIwMjAtMTItMjggMTA6Mzc6MDYuMzM3NjQwIiwidHJhbnNmZXJfb3JkZXJfaXRlbV91aWQiOiJ0cmlfODk0NWM3NzAtYmZiNi00MDFlLTgzNDYtYzIzOTllNzYwM2Q5IiwiYW1vdW50X2NlbnRzIjoyODY3LCJtZXRhZGF0YSI6eyJwbGVkZ19zZXNzaW9uIjp7ImlwIjoiOTEuMTYxLjE4MS4zMiIsInVzZXJfYWdlbnQiOnsic3RyaW5nIjoiTW96aWxsYS81LjAgKFgxMTsgVWJ1bnR1OyBMaW51eCB4ODZfNjQ7IHJ2Ojg0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvODQuMCIsInBsYXRmb3JtIjoibGludXgiLCJicm93c2VyIjoiZmlyZWZveCIsInZlcnNpb24iOiI4NC4wIiwibGFuZ3VhZ2UiOm51bGx9fX19.aQ9sLecFiD4yazL_T2hA-WMdbCQT_I157tn0P2sIoMc"}';
        $secret = 'secret';
        $content = json_decode($jsonContent, true, 512, \JSON_THROW_ON_ERROR);

        $validator = $this->buildWithValidContentAndSecret($content, $secret);

        self::assertTrue($validator->validate($content));
    }

    private function buildWithValidContentAndSecret(array $content, string $secret): ValidatorInterface
    {
        $body = (new HS256Handler())->decode($content['signature']);
        $payment = (new PaymentBuilder())
            ->withId(Reference::fromString($body['reference'])->getPaymentId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->withSecret($secret)
                    ->build()
            )
            ->build();

        $repository = new InMemoryPaymentRepository();
        $repository->add($payment);

        return (new TransferValidatorBuilder())
            ->withPaymentProvider(
                (new PaymentProviderBuilder())
                    ->withRepository($repository)
                    ->build()
            )
            ->build();
    }
}
