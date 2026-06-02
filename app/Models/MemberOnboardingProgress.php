<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberOnboardingProgress extends Model
{
    protected $table = 'member_onboarding_progress';

    protected $fillable = [
        'cooperative_member_id',
        'profile_completed_at',
        'kyc_uploaded_at',
        'first_savings_paid_at',
        'loan_intro_seen_at',
        'reward_intro_seen_at',
        'completed_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'profile_completed_at' => 'datetime',
            'kyc_uploaded_at' => 'datetime',
            'first_savings_paid_at' => 'datetime',
            'loan_intro_seen_at' => 'datetime',
            'reward_intro_seen_at' => 'datetime',
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }
}
