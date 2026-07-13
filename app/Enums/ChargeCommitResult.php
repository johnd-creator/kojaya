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

    /** Late response arrived for the same attempt that was already committed
     *  by an earlier Transaction B — same provider reference. No-op. */
    case AlreadyCommitted = 'ALREADY_COMMITTED';

    /** Late response arrived for the same attempt after recovery already
     *  linked the same charge. No-op, no orphan. */
    case ReconciledSameAttempt = 'RECONCILED_SAME_ATTEMPT';

    /** Response came from a stale attempt — do not commit, do not confirm. */
    case StaleAttempt = 'STALE_ATTEMPT';

    /** Reservation is no longer RESERVED — cannot commit charge. */
    case InvalidReservation = 'INVALID_RESERVATION';

    /** Intent has expired — cannot commit charge. */
    case Expired = 'EXPIRED';

    /** Intent is in a terminal gateway state — cannot commit charge. */
    case Terminal = 'TERMINAL';
}
