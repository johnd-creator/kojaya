<?php

namespace App\Enums;

enum PaymentSettlementStatus: string
{
    case NotSettled = 'NOT_SETTLED';

    case Settling = 'SETTLING';

    case Settled = 'SETTLED';

    case Failed = 'FAILED';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Settled, self::Failed], true);
    }
}
