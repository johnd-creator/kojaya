<?php

namespace App\Enums;

enum PaymentGatewayStatus: string
{
    case New = 'NEW';

    case ChargeCreating = 'CHARGE_CREATING';

    case Pending = 'PENDING';

    case Paid = 'PAID';

    case Failed = 'FAILED';

    case Cancelled = 'CANCELLED';

    case Denied = 'DENIED';

    case Expired = 'EXPIRED';

    /**
     * @return list<string>
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::New => [self::ChargeCreating->value, self::Pending->value],
            self::ChargeCreating => [self::Pending->value, self::Failed->value, self::Cancelled->value],
            self::Pending => [
                self::Paid->value,
                self::Expired->value,
                self::Failed->value,
                self::Cancelled->value,
                self::Denied->value,
            ],
            self::Failed => [self::Paid->value],
            self::Paid, self::Cancelled, self::Denied, self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return in_array($target->value, $this->validTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Denied, self::Expired], true);
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
