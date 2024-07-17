<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification;

use Pledg\SyliusPaymentPlugin\ValueObject\Status;

class StandardContentBuilder
{
    /** @var string */
    protected $createAt;

    /** @var string */
    protected $id;

    /** @var array */
    protected $metadata;

    /** @var Status */
    protected $status;

    /** @var bool */
    protected $sandbox;

    /** @var string */
    protected $error;

    /** @var string */
    protected $reference;

    /** @var string */
    protected $signature;

    public function __construct()
    {
        $this->createdAt = '2019-04-04T12:20:34.97138Z';
        $this->id = 'test-valid';
        $this->metadata = ['foo' => 'bar'];
        $this->status = new Status(Status::COMPLETED);
        $this->sandbox = true;
        $this->error = '';
        $this->reference = 'PLEDGBYSOFINCO_108698_6786391';
        $this->signature = 'ADF3B51FC2920B634302A2D5742CED20BE6175B664E3B1C0D8FFA7FC7A92A975';
    }

    public function withReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function build(): array
    {
        return [
            'created_at' => $this->createdAt,
            'id' => $this->id,
            'metadata' => $this->metadata,
            'status' => (string) $this->status,
            'sandbox' => $this->sandbox,
            'error' => $this->error,
            'reference' => $this->reference,
            'signature' => $this->signature,
        ];
    }
}
