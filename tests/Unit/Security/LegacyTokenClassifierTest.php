<?php

namespace Tests\Unit\Security;

use App\Services\Auth\LegacyTokenClassifier;
use PHPUnit\Framework\TestCase;

class LegacyTokenClassifierTest extends TestCase
{
    public function test_known_profiles_are_classified_without_exposing_token_material(): void
    {
        $classifier = new LegacyTokenClassifier;

        $this->assertSame('member', $classifier->classify(['profile:read', 'member:read', 'member:write']));
        $this->assertSame('ess', $classifier->classify(['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read']));
        $this->assertSame('technician', $classifier->classify(['profile:read', 'work-orders:read', 'work-orders:write', 'work-orders:review']));
        $this->assertSame('admin', $classifier->classify(['profile:read', 'cooperative.member.read', 'reports:read']));
    }

    public function test_wildcard_combined_empty_and_unknown_profiles_require_rotation(): void
    {
        $classifier = new LegacyTokenClassifier;

        $this->assertSame('unsafe', $classifier->classify(['*']));
        $this->assertSame('unsafe', $classifier->classify(['profile:read', 'member:read', 'ess:read']));
        $this->assertSame('unsafe', $classifier->classify([]));
        $this->assertSame('unsafe', $classifier->classify(['profile:read', 'unknown:ability']));
    }
}
