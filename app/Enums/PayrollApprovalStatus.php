<?php

namespace App\Enums;

enum PayrollApprovalStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
