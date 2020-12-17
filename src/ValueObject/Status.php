<?php


namespace Pledg\SyliusPaymentPlugin\ValueObject;

use Payum\Core\Request\GetStatusInterface;

/**
 * Source :
 * https://docs.processout.com/payments/handle-status-changes-webhooks/#transaction-statuses
 */
class Status
{
    public const WAITING = 'waiting';
    public const PENDING = 'pending';
    public const AUTHORIZED = 'authorized';
    public const PENDING_CAPTURE = 'pending-capture';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const VOIDED = 'voided';
    public const REFUNDED = 'refunded';
    public const IN_REVIEW = 'in-review';
    public const BLOCKED = 'blocked';
    public const RETRIEVAL_REQUEST = 'retrieval-request';
    public const FRAUD_NOTIFICATION = 'fraud-notification';
    public const CHARGEBACK_INITIATED = 'chargeback-initiated';
    public const SOLVED = 'solved';
    public const RESERVED = 'reversed';

    /** @var string  */
    protected $value;

    /** @var array  */
    protected static $cache = [];

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function __construct(string $value)
    {
        if ($value instanceof static) {
            $value = (string) $value;
        }

        if (!self::isValid($value)) {
            throw new \UnexpectedValueException("Value '$value' is not part of the enum " . static::class);
        }

        $this->value = $value;
    }

    public function markRequest(GetStatusInterface $getStatus): void
    {
        switch ($this->value) {
            case static::PENDING:
            case static::PENDING_CAPTURE:
                $getStatus->markPending();
                break;
            case static::AUTHORIZED:
                $getStatus->markAuthorized();
                break;
            case static::COMPLETED:
                $getStatus->markCaptured();
                break;
            case static::FAILED:
            case static::BLOCKED:
                $getStatus->markFailed();
                break;
            case static::REFUNDED:
                $getStatus->markRefunded();
                break;
            default: // WAITING, PENDING_CAPTURE, VOIDED, RETRIEVAL_REQUEST, FRAUD_NOTIFICATION, CHARGEBACK_INITIATED, SOLVED, RESERVED
                $getStatus->markUnknown();
        }
    }

    public static function toArray(): array
    {
        $class = static::class;

        if (!isset(static::$cache[$class])) {
            $reflection = new \ReflectionClass($class);
            static::$cache[$class] = $reflection->getConstants();
        }

        return static::$cache[$class];
    }

    public static function isValid(string $value): bool
    {
        return \in_array($value, static::toArray(), true);
    }
}
