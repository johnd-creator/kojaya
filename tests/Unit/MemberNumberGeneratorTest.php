<?php

namespace Tests\Unit;

use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MemberNumberGeneratorTest extends TestCase
{
    use DatabaseMigrations;

    public function test_generate_returns_kop_prefix_with_3_digit_padding(): void
    {
        $generator = new MemberNumberGenerator;

        $result = $generator->generate();

        $this->assertSame('KOP-001', $result);
    }

    public function test_generate_increments_from_highest_existing(): void
    {
        CooperativeMember::factory()->create([
            'no_anggota' => 'KOP-005',
            'member_no' => 'KOP-005',
        ]);

        $generator = new MemberNumberGenerator;

        $this->assertSame('KOP-006', $generator->generate());
    }

    public function test_generate_ignores_non_kop_formats(): void
    {
        CooperativeMember::factory()->create([
            'no_anggota' => 'MBR-123456',
            'member_no' => 'MBR-123456',
        ]);
        CooperativeMember::factory()->create([
            'no_anggota' => 'TMP0ABCDEF',
            'member_no' => 'TMP0ABCDEF',
        ]);
        CooperativeMember::factory()->create([
            'no_anggota' => 'KOP-2022-00001',
            'member_no' => 'KOP-2022-00001',
        ]);

        $generator = new MemberNumberGenerator;

        $this->assertSame('KOP-001', $generator->generate());
    }

    public function test_generate_auto_expands_beyond_3_digits(): void
    {
        CooperativeMember::factory()->create([
            'no_anggota' => 'KOP-999',
            'member_no' => 'KOP-999',
        ]);

        $generator = new MemberNumberGenerator;

        $this->assertSame('KOP-1000', $generator->generate());
    }

    public function test_generate_includes_soft_deleted(): void
    {
        $member = CooperativeMember::factory()->create([
            'no_anggota' => 'KOP-010',
            'member_no' => 'KOP-010',
        ]);
        $member->delete();

        $generator = new MemberNumberGenerator;

        $this->assertSame('KOP-011', $generator->generate());
    }
}
