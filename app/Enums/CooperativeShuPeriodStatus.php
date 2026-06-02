<?php

namespace App\Enums;

enum CooperativeShuPeriodStatus: string
{
    case Draft = 'DRAFT';
    case Closed = 'CLOSED';
    case Revision = 'REVISION';
    case ClosedRevised = 'CLOSED_REVISED';
}
