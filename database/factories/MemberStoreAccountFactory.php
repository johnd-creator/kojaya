<?php

namespace Database\Factories;

use App\Enums\MemberStoreAccountStatus;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberStoreAccount>
 */
class MemberStoreAccountFactory extends Factory
{
    protected $model = MemberStoreAccount::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'cooperative_member_id' => CooperativeMember::factory(),
            'balance' => 0,
            'credit_limit' => 0,
            'status' => MemberStoreAccountStatus::Active->value,
            'opened_at' => now(),
        ];
    }

    public function withLimit(int $limit): static
    {
        return $this->state(fn (array $attributes): array => ['credit_limit' => $limit]);
    }
}
