<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification;


use Pledg\SyliusPaymentPlugin\ValueObject\Status;

class StandardContentBuilder
{
    /** @var string */
    protected $createAt;

    /** @var string */
    protected $id;

    /** @var array */
    protected $additionalData;

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
        $this->additionalData = ['xx' => 'yy' ];
        $this->metadata = ['foo' => 'bar'];
        $this->status = new Status(Status::COMPLETED);
        $this->sandbox = true;
        $this->error = '';
        $this->reference = 'PLEDG_1086986786391';
        $this->signature = 'B1C777835C01CA96AC4C3097FD46A7CA49B92BE157EDE0CB3552880D12A15359';
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
            'additional_data' => $this->additionalData,
            'metadata' => $this->metadata,
            'status' => (string) $this->status,
            'sandbox' => $this->sandbox,
            'error' => $this->error,
            'reference' => $this->reference,
            'signature' => $this->signature
        ];
    }
}
