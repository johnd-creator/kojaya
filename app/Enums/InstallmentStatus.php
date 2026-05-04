<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Pending = 'PENDING';
    case Partial = 'PARTIAL';
    case Paid = 'PAID';
    case Overdue = 'OVERDUE';
}
