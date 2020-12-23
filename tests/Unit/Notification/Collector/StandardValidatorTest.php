<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use Doctrine\ORM\ORMException;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ValidatorInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\StandardContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Provider\PaymentProviderBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Model\PaymentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Sylius\Repository\InMemoryPaymentRepository;
use Tests\Pledg\SyliusPaymentPlugin\Unit\ValueObject\MerchantBuilder;

class StandardValidatorTest extends TestCase
{
    /** @test */
    public function it_does_not_support_invalid_content(): void
    {
        self::assertFalse((new StandardValidatorBuilder())->build()->supports(['signature']));
    }

    /** @test */
    public function it_support_valid_content(): void
    {
        $content = (new StandardContentBuilder())->build();

        self::assertTrue((new StandardValidatorBuilder())->build()->supports($content));
    }

    /** @test */
    public function it_validates_valid_content(): void
    {
        $content = (new StandardContentBuilder())->build();
        $validator = $this->buildWithValidContent($content);

        self::assertTrue($validator->validate($content));

    }

    /** @test */
    public function it_throws_exception_when_payment_does_not_exists(): void
    {
        $content = (new StandardContentBuilder())->build();
        $validator = (new StandardValidatorBuilder())->build();

        $this->expectException(ORMException::class);

        $validator->validate($content);
    }

    private function buildWithValidContent(array $content): ValidatorInterface
    {
        $payment = (new PaymentBuilder())
            ->withId(Reference::fromString($content['reference'])->getPaymentId())
            ->withMerchant(
                (new MerchantBuilder())
                    ->withSecret('SECRET')
                    ->build()
            )
            ->build();

        $repository = new InMemoryPaymentRepository();
        $repository->add($payment);

        return (new StandardValidatorBuilder())
            ->withPaymentProvider(
                (new PaymentProviderBuilder())
                    ->withRepository($repository)
                    ->build()
            )
            ->build();
    }
}
