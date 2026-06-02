<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Processed = 'PROCESSED';
}
