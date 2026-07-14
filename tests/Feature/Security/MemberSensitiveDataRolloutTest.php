<?php

namespace Tests\Feature\Security;

use App\Exceptions\PiiDecryptionException;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberProfileService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Encryption\Encrypter;
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

        $this->assertSame('3201-2345 6789 0001', $raw['identity_number']);
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
            'identity_number_enc' => null,
            'identity_number_key_version' => null,
            'identity_number_bidx' => null,
            'identity_number_bidx_version' => null,
            'identity_number_migrated_at' => null,
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

    public function test_backfill_writes_encryption_and_preserves_plaintext_during_dual_write(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => '3201234567890001',
            'identity_number_enc' => null,
            'identity_number_key_version' => null,
            'identity_number_bidx' => null,
            'identity_number_bidx_version' => null,
            'identity_number_migrated_at' => null,
        ]);

        $this->artisan('members:backfill-sensitive-data', [
            '--limit' => 1,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertSame('3201234567890001', $raw['identity_number']);
        $this->assertNotNull($raw['identity_number_enc']);
        $this->assertSame('v1', $raw['identity_number_key_version']);
        $this->artisan('members:verify-sensitive-data')->assertExitCode(0);
    }

    public function test_verification_fails_for_a_missing_plaintext_compatibility_copy(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => null,
        ]);
        $report = tempnam(sys_get_temp_dir(), 'pii-verify-');

        $this->artisan('members:verify-sensitive-data', [
            '--report' => $report,
        ])->assertExitCode(1);

        $contents = (string) file_get_contents($report);
        $this->assertStringContainsString('missing_plaintext_compatibility_copy', $contents);
        $this->assertStringNotContainsString('3201234567890001', $contents);
        @unlink($report);
    }

    public function test_dry_run_reports_missing_compatibility_copy_and_normal_backfill_repairs_it(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => null,
        ]);
        $report = tempnam(sys_get_temp_dir(), 'pii-backfill-');

        $this->artisan('members:backfill-sensitive-data', [
            '--dry-run' => true,
            '--report' => $report,
        ])->assertExitCode(0);

        $this->assertNull(DB::table('cooperative_members')->where('id', $member->id)->value('identity_number'));
        $contents = (string) file_get_contents($report);
        $this->assertStringContainsString('missing_plaintext_compatibility_copy', $contents);
        $this->assertStringNotContainsString('3201234567890001', $contents);

        $this->artisan('members:backfill-sensitive-data', [
            '--report' => $report,
        ])->assertExitCode(0);

        $this->assertSame(
            '3201234567890001',
            DB::table('cooperative_members')->where('id', $member->id)->value('identity_number'),
        );
        $this->artisan('members:verify-sensitive-data')->assertExitCode(0);
        @unlink($report);
    }

    public function test_plaintext_retirement_requires_explicit_phase_and_confirmation(): void
    {
        $member = CooperativeMember::factory()->create();
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => '3201234567890001',
        ]);

        $this->artisan('members:backfill-sensitive-data', [
            '--retire-plaintext' => true,
            '--confirm-retirement' => true,
        ])->assertExitCode(1);

        $this->assertSame('3201234567890001', DB::table('cooperative_members')->where('id', $member->id)->value('identity_number'));
    }

    public function test_plaintext_retirement_requires_clean_parity_and_then_clears_plaintext(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);

        config(['security.rollout_phase' => 'plaintext_retired']);
        $this->app->forgetInstance(\App\Services\Security\PiiCryptoService::class);

        $this->artisan('members:backfill-sensitive-data', [
            '--retire-plaintext' => true,
            '--confirm-retirement' => true,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertNull($raw['identity_number']);
        $this->artisan('members:verify-sensitive-data')->assertExitCode(0);
    }

    public function test_encrypted_preferred_keeps_plaintext_for_application_rollback(): void
    {
        config(['security.rollout_phase' => 'encrypted_preferred']);
        $this->app->forgetInstance(\App\Services\Security\PiiCryptoService::class);

        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();

        $this->assertSame('3201234567890001', $raw['identity_number']);
        $this->assertNotNull($raw['identity_number_enc']);
        $this->assertSame('3201234567890001', $member->refresh()->identity_number);
    }

    public function test_verification_rejects_envelope_and_metadata_version_mismatch_without_pii_in_report(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number_key_version' => 'v2',
        ]);
        $report = tempnam(sys_get_temp_dir(), 'pii-verify-');

        $this->artisan('members:verify-sensitive-data', [
            '--report' => $report,
        ])->assertExitCode(1);

        $contents = (string) file_get_contents($report);
        $this->assertStringContainsString('envelope_version_mismatch', $contents);
        $this->assertStringNotContainsString('3201234567890001', $contents);
        @unlink($report);
    }

    public function test_backfill_is_idempotent_for_a_complete_dual_write_row(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        $firstCiphertext = DB::table('cooperative_members')->where('id', $member->id)->value('identity_number_enc');

        $this->artisan('members:backfill-sensitive-data', ['--limit' => 1])->assertExitCode(0);

        $this->assertSame(
            $firstCiphertext,
            DB::table('cooperative_members')->where('id', $member->id)->value('identity_number_enc'),
        );
    }

    public function test_backfill_can_rotate_readable_rows_to_current_encryption_and_index_versions(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        $encryptionV1 = config('security.encryption_keys.v1');
        $blindIndexV1 = config('security.blind_index_keys.v1');

        config([
            'security.encryption_keys' => [
                'v1' => $encryptionV1,
                'v2' => $this->key('encryption-v2'),
            ],
            'security.encryption_current_version' => 'v2',
            'security.blind_index_keys' => [
                'v1' => $blindIndexV1,
                'v2' => $this->key('blind-index-v2'),
            ],
            'security.blind_index_current_version' => 'v2',
            'security.blind_index_active_versions' => ['v1', 'v2'],
        ]);
        $this->app->forgetInstance(\App\Services\Security\PiiCryptoService::class);

        $this->artisan('members:backfill-sensitive-data', [
            '--rotate-to-current' => true,
            '--limit' => 1,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertSame('v2', $raw['identity_number_key_version']);
        $this->assertSame('v2', $raw['identity_number_bidx_version']);
        $this->assertSame('3201234567890001', $raw['identity_number']);
    }

    public function test_legacy_encrypted_only_rotation_restores_plaintext_compatibility(): void
    {
        $member = CooperativeMember::factory()->create();
        $value = '3201234567890001';
        $legacyKey = (string) config('security.legacy_encryption_key');
        $legacyEncrypter = new Encrypter(
            base64_decode(substr($legacyKey, 7), true),
            'aes-256-cbc',
        );

        DB::table('cooperative_members')->where('id', $member->id)->update([
            'identity_number' => null,
            'identity_number_enc' => $legacyEncrypter->encryptString($value),
            'identity_number_key_version' => null,
            'identity_number_bidx' => null,
            'identity_number_bidx_version' => null,
            'identity_number_migrated_at' => null,
        ]);

        $this->artisan('members:backfill-sensitive-data', [
            '--rotate-to-current' => true,
            '--limit' => 1,
        ])->assertExitCode(0);

        $raw = (array) DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertSame($value, $raw['identity_number']);
        $this->assertSame('v1', $raw['identity_number_key_version']);
        $this->assertSame('v1', $raw['identity_number_bidx_version']);
        $this->assertSame('v1', app(\App\Services\Security\PiiCryptoService::class)->envelopeVersion($raw['identity_number_enc']));
        $this->assertSame($value, $raw['identity_number']);
    }

    public function test_backfill_reloads_the_row_after_candidate_selection(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
        ]);
        $reloadedValue = '3201999999999999';
        $updated = false;

        DB::listen(function (QueryExecuted $query) use (&$updated, $member, $reloadedValue): void {
            if ($updated || ! str_starts_with(strtolower(trim($query->sql)), 'select "id"')) {
                return;
            }

            $updated = true;
            DB::table('cooperative_members')->where('id', $member->id)->update([
                'identity_number' => $reloadedValue,
                'identity_number_enc' => null,
                'identity_number_key_version' => null,
                'identity_number_bidx' => null,
                'identity_number_bidx_version' => null,
                'identity_number_migrated_at' => null,
            ]);
        });

        $this->artisan('members:backfill-sensitive-data', ['--limit' => 1])->assertExitCode(0);

        $this->assertSame(
            $reloadedValue,
            DB::table('cooperative_members')->where('id', $member->id)->value('identity_number'),
        );
    }

    public function test_omitted_sensitive_profile_values_are_preserved_and_explicit_null_clears_one_field(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'npwp' => '123456789012000',
            'no_rekening' => '9876543210',
        ]);

        app(MemberProfileService::class)->update($user, $member, [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $this->assertSame('123456789012000', $member->refresh()->npwp);
        $this->assertSame('9876543210', $member->no_rekening);

        app(MemberProfileService::class)->update($user, $member, [
            'name' => 'Updated Name',
            'email' => $user->email,
            'npwp' => null,
        ]);

        $this->assertNull($member->refresh()->npwp);
        $this->assertSame('9876543210', $member->no_rekening);
        $this->assertStringNotContainsString(
            '123456789012000',
            DB::table('audit_logs')->pluck('new_values')->implode(' '),
        );
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }
}
