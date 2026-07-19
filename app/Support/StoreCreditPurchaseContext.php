<?php

namespace App\Support;

use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;

final readonly class StoreCreditPurchaseContext
{
    public function __construct(
        public MemberStoreAccount $account,
        public ?MemberStoreDelegate $delegate,
        public int $amount,
    ) {}
}
