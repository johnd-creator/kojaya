<?php

namespace App\Services\Cooperative;

use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Models\CooperativeMember;

class MemberAccessService
{
    /**
     * @return array{
     *     status: string,
     *     validation_status: ?string,
     *     experience: string,
     *     lifecycle_experience: string,
     *     review_state: string,
     *     is_active: bool,
     *     is_pending_review: bool,
     *     can_access_financial_features: bool,
     *     can_preview_financial_summary: bool,
     *     can_access_onboarding: bool,
     *     can_view_lifecycle_status: bool,
     *     can_edit_safe_profile: bool,
     *     can_access_profile: bool,
     *     can_access_notifications: bool
     * }|null
     */
    public function for(?CooperativeMember $member): ?array
    {
        if ($member === null) {
            return null;
        }

        $experience = $this->experience($member);
        $isActive = $experience->isActive();
        $isPendingReview = $experience === MemberLifecycleExperience::UnderReview;

        $canViewLifecycleStatus = in_array($experience, [
            MemberLifecycleExperience::WaitingVerification,
            MemberLifecycleExperience::UnderReview,
            MemberLifecycleExperience::RevisionRequired,
            MemberLifecycleExperience::Rejected,
        ], true);

        $canEditSafeProfile = in_array($experience, [
            MemberLifecycleExperience::WaitingVerification,
            MemberLifecycleExperience::RevisionRequired,
        ], true);

        return [
            'status' => (string) $member->status,
            'validation_status' => $member->validation_status,
            'experience' => $experience->value,
            'lifecycle_experience' => $experience->value,
            'review_state' => $experience->reviewState(),
            'is_active' => $isActive,
            'is_pending_review' => $isPendingReview,
            'can_access_financial_features' => $isActive,
            'can_preview_financial_summary' => $isActive || $isPendingReview,
            'can_access_onboarding' => $canViewLifecycleStatus,
            'can_view_lifecycle_status' => $canViewLifecycleStatus,
            'can_edit_safe_profile' => $canEditSafeProfile,
            'can_access_profile' => true,
            'can_access_notifications' => true,
        ];
    }

    public function experience(?CooperativeMember $member): MemberLifecycleExperience
    {
        return MemberLifecycleExperience::fromMember($member);
    }

    public function lifecycleExperience(?CooperativeMember $member): string
    {
        return $this->experience($member)->value;
    }
}
