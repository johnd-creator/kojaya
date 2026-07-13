<?php

namespace App\Enums;

/**
 * Classified outcome of a provider charge creation attempt.
 *
 * DefinitiveNotCreated: provider confirmed no charge was created (e.g.
 *   malformed/empty 2xx, application-level "not created" response). Safe
 *   to retry the same attempt.
 *
 * DefinitiveRejected: provider definitively rejected the charge (e.g.
 *   HTTP 4xx, channel not activated). Mark the attempt as FAILED.
 *
 * Unknown: the outcome is ambiguous (timeout, connection failure, HTTP
 *   5xx). The charge may or may not have been created. The intent must
 *   stay blocked (CHARGE_CREATING), the attempt marked UNKNOWN, and a
 *   reconciliation incident created.
 */
enum ProviderChargeOutcome: string
{
    case DefinitiveNotCreated = 'DEFINITIVE_NOT_CREATED';

    case DefinitiveRejected = 'DEFINITIVE_REJECTED';

    case Unknown = 'UNKNOWN';
}
