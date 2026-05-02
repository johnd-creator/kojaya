<?php

namespace App\Services\Cooperative;

use App\Models\Organization;
use Illuminate\Support\Str;

class CooperativeHeadOfficeResolver
{
    public function resolve(): Organization
    {
        return Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Jaya Bersama No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasijayabersama.id',
                'is_active' => true,
            ],
        );
    }
}
