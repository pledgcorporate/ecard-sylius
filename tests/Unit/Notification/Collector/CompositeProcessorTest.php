<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\Collector;

use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\Notification\Collector\CompositeProcessor;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\StandardContentBuilder;
use Tests\Pledg\SyliusPaymentPlugin\Unit\Notification\TransferContentBuilder;

class CompositeProcessorTest extends TestCase
{
    /** @test */
    public function it_supports_standard_content_with_not_existing_payment(): void
    {
        $content = (new StandardContentBuilder())->build();
        $processor = $this->buildProcessor();

        $this->expectException(EntityNotFoundException::class);

        $processor->process($content);
    }

    /** @test */
    public function it_supports_transfer_content_with_not_existing_payment(): void
    {
        $content = (new TransferContentBuilder())->withValidContent()->build();
        $processor = $this->buildProcessor();

        $this->expectException(EntityNotFoundException::class);

        $processor->process($content);
    }

    private function buildProcessor(): ProcessorInterface
    {
        return new CompositeProcessor(new \ArrayIterator([
            (new StandardProcessorBuilder())->build(),
            (new TransferProcessorBuilder())->build(),
        ]));
    }
}
