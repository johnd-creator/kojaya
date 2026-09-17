<?php

declare(strict_types=1);

namespace App\Enums\Cooperative;

use App\Models\CooperativeMember;

enum MemberLifecycleExperience: string
{
    case WaitingVerification = 'WAITING_VERIFICATION';
    case UnderReview = 'UNDER_REVIEW';
    case RevisionRequired = 'REVISION_REQUIRED';
    case Rejected = 'REJECTED';
    case Active = 'ACTIVE';
    case BlockedUnknown = 'BLOCKED_UNKNOWN';

    public static function fromMember(?CooperativeMember $member): self
    {
        if (! $member) {
            return self::BlockedUnknown;
        }

        return self::fromStatuses($member->status, $member->validation_status);
    }

    public static function fromStatuses(?string $status, ?string $validationStatus): self
    {
        return match ([$status, $validationStatus]) {
            [CooperativeMember::VALIDATION_PENDING, CooperativeMember::VALIDATION_PENDING] => self::WaitingVerification,
            [CooperativeMember::VALIDATION_PENDING, CooperativeMember::VALIDATION_PENDING_REVIEW],
            [CooperativeMember::VALIDATION_PENDING, 'PENDING_REVIEW'] => self::UnderReview,
            [CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_REVISION] => self::RevisionRequired,
            [CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_REJECTED],
            [CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_INACTIVE],
            [CooperativeMember::VALIDATION_RESIGNED, CooperativeMember::VALIDATION_RESIGNED] => self::Rejected,
            [CooperativeMember::VALIDATION_ACTIVE, CooperativeMember::VALIDATION_ACTIVE] => self::Active,
            default => self::BlockedUnknown,
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isNonActiveLifecycle(): bool
    {
        return in_array($this, [
            self::WaitingVerification,
            self::UnderReview,
            self::RevisionRequired,
            self::Rejected,
        ], true);
    }

    public function isBlocked(): bool
    {
        return $this === self::BlockedUnknown;
    }

    public function label(): string
    {
        return match ($this) {
            self::WaitingVerification => 'Menunggu Verifikasi Admin',
            self::UnderReview => 'Menunggu Approval Pengurus',
            self::RevisionRequired => 'Perlu Revisi',
            self::Rejected => 'Ditolak',
            self::Active => 'Aktif',
            self::BlockedUnknown => 'Akses Dibatasi',
        };
    }
}
