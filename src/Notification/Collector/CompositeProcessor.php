<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

class CompositeProcessor implements ProcessorInterface
{
    /** @var iterable|ProcessorInterface[] */
    protected $processors;

    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    public function process(array $content): void
    {
        foreach ($this->processors as $processor) {
            try {
                $processor->process($content);

                return;
            } catch (NotSupportedException $e) {
            }
        }

        throw NotSupportedException::fromContent($content);
    }
}
