<?php

namespace App\Enums;

enum AccountLinkReasonCode: string
{
    case BUSINESS_VERIFICATION = 'business_verification';
    case REGULATORY_REQUEST = 'regulatory_request';
    case MEMBER_CORRECTION = 'member_correction';
    case INTERNAL_AUDIT = 'internal_audit';
    case OTHER = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
