<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case Conflict = 'CONFLICT';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case BusinessRuleViolation = 'BUSINESS_RULE_VIOLATION';
    case PeriodLocked = 'PERIOD_LOCKED';
    case InsufficientBalance = 'INSUFFICIENT_BALANCE';
    case MemberNotActive = 'MEMBER_NOT_ACTIVE';
    case ServerError = 'SERVER_ERROR';
}
