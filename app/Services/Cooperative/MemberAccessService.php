<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;

class MemberAccessService
{
    /**
     * @return array{
     *     status: string,
     *     validation_status: ?string,
     *     is_active: bool,
     *     is_pending_review: bool,
     *     can_access_financial_features: bool,
     *     can_preview_financial_summary: bool,
     *     can_access_onboarding: bool,
     *     can_access_profile: bool,
     *     can_access_notifications: bool
     * }|null
     */
    public function for(?CooperativeMember $member): ?array
    {
        if ($member === null) {
            return null;
        }

        $validationStatus = $member->validation_status ?: $member->status;
        $isActive = $member->status === CooperativeMember::VALIDATION_ACTIVE
            && $member->validation_status === CooperativeMember::VALIDATION_ACTIVE;
        $isPendingReview = in_array($member->validation_status, [
            CooperativeMember::VALIDATION_PENDING_REVIEW,
            'PENDING_REVIEW',
        ], true) && $member->onboarding_submitted_at !== null;
        $canAccessOnboarding = in_array($validationStatus, [
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW,
            CooperativeMember::VALIDATION_REVISION,
            'PENDING_REVIEW',
        ], true);

        return [
            'status' => (string) $member->status,
            'validation_status' => $member->validation_status,
            'is_active' => $isActive,
            'is_pending_review' => $isPendingReview,
            'can_access_financial_features' => $isActive,
            'can_preview_financial_summary' => $isActive || $isPendingReview,
            'can_access_onboarding' => $canAccessOnboarding,
            'can_access_profile' => true,
            'can_access_notifications' => true,
        ];
    }
}
