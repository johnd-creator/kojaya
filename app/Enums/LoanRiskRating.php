<?php

namespace App\Enums;

enum LoanRiskRating: string
{
    case Low = 'LOW';
    case Medium = 'MEDIUM';
    case High = 'HIGH';
    case Npl = 'NPL';
    case WrittenOff = 'WRITTEN_OFF';
}
