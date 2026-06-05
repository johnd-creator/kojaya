<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'DRAFT';
    case Approved = 'APPROVED';
    case Processed = 'PROCESSED';
    case Paid = 'PAID';
}
