<?php

namespace App\Enums;

enum TokenApp: string
{
    case MEMBER = 'member';
    case ESS = 'ess';
    case TECHNICIAN = 'technician';
    case ADMIN = 'admin';
}
