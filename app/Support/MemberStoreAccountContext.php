<?php

namespace App\Support;

use App\Models\User;

final readonly class MemberStoreAccountContext
{
    public function __construct(
        public string $organizationId,
        public int $cooperativeMemberId,
        public int $creditLimit = 0,
        public int $openingBalance = 0,
        public ?User $openedBy = null,
        public ?string $reason = null,
        public ?string $idempotencyKey = null,
    ) {}
}
