<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

use Doctrine\ORM\ORMException;

interface ProcessorInterface
{
    /**
     * @throws NotSupportedException
     * @throws InvalidSignatureException
     * @throws ORMException
     */
    public function process(array $content): void;
}
