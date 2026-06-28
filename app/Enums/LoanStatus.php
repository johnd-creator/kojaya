<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Applied = 'APPLIED';
    case ManagerApproved = 'MANAGER_APPROVED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Active = 'ACTIVE';
    case PaidOff = 'PAID_OFF';
    case Defaulted = 'DEFAULTED';
    case WrittenOff = 'WRITTEN_OFF';
}
