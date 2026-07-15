<?php

namespace App\Enums;

enum OrganizationVisibilityState: string
{
    case GLOBAL = 'global';
    case ORGANIZATION = 'organization';
    case DENIED = 'denied';
}
