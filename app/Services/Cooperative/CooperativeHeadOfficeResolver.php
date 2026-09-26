<?php

namespace App\Services\Cooperative;

use App\Models\Organization;
use Illuminate\Support\Str;
use LogicException;

class CooperativeHeadOfficeResolver
{
    public function resolve(): Organization
    {
        $this->assertBootstrapIdentityConfigured();

        return Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => Str::uuid(),
                'name' => trim((string) config('cooperative.bootstrap_organization.name')),
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => config('cooperative.bootstrap_organization.address'),
                'phone' => config('cooperative.bootstrap_organization.phone'),
                'email' => config('cooperative.bootstrap_organization.email'),
                'is_active' => true,
            ],
        );
    }

    public function assertBootstrapIdentityConfigured(): void
    {
        if (Organization::query()->where('code', 'KOP-001')->exists()) {
            return;
        }

        if (blank(config('cooperative.bootstrap_organization.name'))) {
            throw new LogicException(
                'KOP-001 does not exist and COOPERATIVE_BOOTSTRAP_ORGANIZATION_NAME is not configured. Set the approved cooperative name before production-like seeding.',
            );
        }
    }
}
