<?php

namespace Tests\Unit\Procurement;

use App\Models\User;
use App\Services\Procurement\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_levels_based_on_amount(): void
    {
        $svc = new ApprovalService;
        $this->assertEquals([1], $svc->requiredLevels(1000000));
        $this->assertEquals([1, 2], $svc->requiredLevels(100000000));
        $this->assertEquals([1, 2, 3], $svc->requiredLevels(500000000));
    }

    public function test_can_approve_based_on_role_and_level(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')->andReturnUsing(function ($roles) {
            return in_array('Manager', (array) $roles, true);
        });

        $svc = new ApprovalService;

        $this->assertTrue($svc->canApprove($user, 1));
        $this->assertTrue($svc->canApprove($user, 2));
        $this->assertFalse($svc->canApprove($user, 3));
    }
}
