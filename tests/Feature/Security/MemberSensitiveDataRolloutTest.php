<?php

namespace Tests\Feature\Security;

use App\Exceptions\PiiDecryptionException;
use App\Models\CooperativeMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberSensitiveDataRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_sensitive_values_are_encrypted_versioned_and_indexed(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201-2345 6789 0001',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234 5678',
        ]);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();

        $this->assertNull($raw['identity_number']);
        $this->assertSame('v1', $raw['identity_number_key_version']);
        $this->assertSame('v1', $raw['identity_number_bidx_version']);
        $this->assertNotNull($raw['identity_number_enc']);
        $this->assertSame('3201-2345 6789 0001', $member->refresh()->identity_number);
        $this->assertSame('12.345.678.9-012.000', $member->npwp);
        $this->assertSame('1234 5678', $member->no_rekening);
    }

    public function test_encrypted_value_does_not_fallback_to_legacy_plaintext_on_failure(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => 'legacy-value',
            'identity_number_enc' => 'corrupt-ciphertext',
            'identity_number_key_version' => 'v1',
        ]);

        $this->expectException(PiiDecryptionException::class);

        $member->refresh()->identity_number;
    }

    public function test_backfill_dry_run_does_not_write_and_report_contains_no_pii(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => '3201234567890001',
        ]);
        $report = tempnam(sys_get_temp_dir(), 'pii-report-');

        $this->artisan('members:backfill-sensitive-data', [
            '--dry-run' => true,
            '--report' => $report,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertSame('3201234567890001', $raw['identity_number']);
        $this->assertStringNotContainsString('3201234567890001', (string) file_get_contents($report));
        @unlink($report);
    }

    public function test_backfill_writes_legacy_values_and_verification_rejects_remaining_plaintext(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => '3201234567890001',
        ]);

        $this->artisan('members:backfill-sensitive-data', [
            '--limit' => 1,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertNull($raw['identity_number']);
        $this->assertNotNull($raw['identity_number_enc']);
        $this->assertSame('v1', $raw['identity_number_key_version']);
        $this->artisan('members:verify-sensitive-data')->assertExitCode(0);
    }

    public function test_verification_fails_when_plaintext_remains(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => '3201234567890001',
        ]);
        $report = tempnam(sys_get_temp_dir(), 'pii-verify-');

        $this->artisan('members:verify-sensitive-data', [
            '--report' => $report,
        ])->assertExitCode(1);

        $contents = (string) file_get_contents($report);
        $this->assertStringContainsString('plaintext_remaining', $contents);
        $this->assertStringNotContainsString('3201234567890001', $contents);
        @unlink($report);
    }
}
