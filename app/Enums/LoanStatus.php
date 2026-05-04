<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Applied = 'APPLIED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Active = 'ACTIVE';
    case PaidOff = 'PAID_OFF';
    case Defaulted = 'DEFAULTED';
}
