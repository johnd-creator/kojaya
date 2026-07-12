<?php

namespace App\Enums;

/**
 * Typed result of Transaction B (charge commit).
 *
 * Only Committed permits marking the charge attempt as CONFIRMED.
 * All other variants must persist provider evidence and create
 * reconciliation incidents where appropriate.
 */
enum ChargeCommitResult: string
{
    /** Charge successfully attached to the authoritative intent. */
    case Committed = 'COMMITTED';

    /** Response came from a stale attempt — do not commit, do not confirm. */
    case StaleAttempt = 'STALE_ATTEMPT';

    /** Reservation is no longer RESERVED — cannot commit charge. */
    case InvalidReservation = 'INVALID_RESERVATION';

    /** Intent has expired — cannot commit charge. */
    case Expired = 'EXPIRED';

    /** Intent is in a terminal gateway state — cannot commit charge. */
    case Terminal = 'TERMINAL';
}
