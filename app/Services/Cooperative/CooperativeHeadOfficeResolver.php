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
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Koperasi No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasi.id',
                'is_active' => true,
            ],
        );
    }
}
