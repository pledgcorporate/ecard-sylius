<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\StandardContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\TransferContentBuilder;
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
        $secret = 'secret';
        $content = (new TransferContentBuilder())->withValidContent()->build();

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
