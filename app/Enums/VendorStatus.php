<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Suspended = 'SUSPENDED';
    case Blacklisted = 'BLACKLISTED';
}
