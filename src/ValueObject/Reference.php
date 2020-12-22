<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\ValueObject;

use Webmozart\Assert\Assert;

class Reference
{
    /** @var int */
    private $id;

    private const PREFIX = 'PLEDG_';

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s%d', self::PREFIX, $this->id);
    }

    public static function fromString(string $reference): self
    {
        $id = (int) str_replace(self::PREFIX, '', $reference);
        $vo = new self($id);

        Assert::eq($reference, (string) $vo);

        return $vo;
    }
}
