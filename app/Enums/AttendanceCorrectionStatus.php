<?php

namespace App\Enums;

enum AttendanceCorrectionStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
