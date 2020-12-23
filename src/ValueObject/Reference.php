<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\ValueObject;

use Webmozart\Assert\Assert;

class Reference
{
    /** @var int */
    private $paymentId;

    /** @var int */
    private $orderId;

    private const PREFIX = 'PLEDG';

    public function __construct(int $orderId, int $paymentId)
    {
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function __toString(): string
    {
        return sprintf('%s_%d_%d', self::PREFIX, $this->orderId, $this->paymentId);
    }

    public static function fromString(string $reference): self
    {
        $parts = explode('_', $reference);

        Assert::count($parts, 3, sprintf('The reference %s is invalid the format should be PLEG_ORDERID_PAYMENTID', $reference));
        Assert::eq($parts[0], self::PREFIX);
        $vo = new self((int) $parts[1], (int) $parts[2]);

        Assert::eq($reference, (string) $vo, sprintf('The reference is invalid : %s provide %s expected', $reference, (string) $vo));

        return $vo;
    }
}
