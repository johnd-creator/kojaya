<?php

namespace App\Enums;

enum PaymentReservationStatus: string
{
    case None = 'NONE';

    case Reserved = 'RESERVED';

    case Consumed = 'CONSUMED';

    case Released = 'RELEASED';

    case Expired = 'EXPIRED';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Consumed, self::Released, self::Expired], true);
    }

    public function isActive(): bool
    {
        return $this === self::Reserved;
    }
}
