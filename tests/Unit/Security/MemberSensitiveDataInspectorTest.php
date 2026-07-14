<?php

namespace Tests\Unit\Security;

use App\Services\Security\MemberSensitiveDataInspector;
use App\Services\Security\PiiCryptoService;
use PHPUnit\Framework\TestCase;

class MemberSensitiveDataInspectorTest extends TestCase
{
    public function test_blind_index_verification_uses_the_recorded_version(): void
    {
        $crypto = $this->crypto();
        $inspector = new MemberSensitiveDataInspector($crypto);
        $value = '3201234567890001';

        $inspection = $inspector->inspect([
            'id' => 'member-1',
            'identity_number' => $value,
            'identity_number_enc' => $crypto->encrypt($value, 'v1'),
            'identity_number_key_version' => 'v1',
            'identity_number_bidx' => $crypto->blindIndexForVersion('identity_number', $value, 'v1'),
            'identity_number_bidx_version' => 'v1',
            'identity_number_migrated_at' => '2026-07-14 00:00:00',
        ]);

        $this->assertSame([], $inspection['fields']['identity_number']['issues']);
    }

    public function test_envelope_metadata_mismatch_is_reported_without_exposing_value(): void
    {
        $crypto = $this->crypto();
        $inspection = (new MemberSensitiveDataInspector($crypto))->inspect([
            'id' => 'member-1',
            'identity_number' => null,
            'identity_number_enc' => $crypto->encrypt('3201234567890001', 'v1'),
            'identity_number_key_version' => 'v2',
            'identity_number_bidx' => $crypto->blindIndexForVersion('identity_number', '3201234567890001', 'v1'),
            'identity_number_bidx_version' => 'v1',
            'identity_number_migrated_at' => '2026-07-14 00:00:00',
        ]);

        $this->assertContains('envelope_version_mismatch', $inspection['fields']['identity_number']['issues']);
        $this->assertArrayNotHasKey('value', $inspection['fields']['identity_number']);
    }

    public function test_missing_plaintext_compatibility_copy_is_reported_before_retirement(): void
    {
        $crypto = $this->crypto();
        $inspection = (new MemberSensitiveDataInspector($crypto))->inspect([
            'id' => 'member-1',
            'identity_number' => null,
            'identity_number_enc' => $crypto->encrypt('3201234567890001'),
            'identity_number_key_version' => 'v2',
            'identity_number_bidx' => $crypto->blindIndexForVersion('identity_number', '3201234567890001', 'v2'),
            'identity_number_bidx_version' => 'v2',
            'identity_number_migrated_at' => '2026-07-14 00:00:00',
        ]);

        $this->assertContains(
            'missing_plaintext_compatibility_copy',
            $inspection['fields']['identity_number']['issues'],
        );
    }

    public function test_missing_plaintext_compatibility_copy_is_not_reported_after_retirement(): void
    {
        $crypto = new PiiCryptoService(
            ['v1' => $this->key('encryption-v1'), 'v2' => $this->key('encryption-v2')],
            'v2',
            ['v1' => $this->key('blind-index-v1'), 'v2' => $this->key('blind-index-v2')],
            'v2',
            null,
            ['v1', 'v2'],
            PiiCryptoService::ROLLOUT_PLAINTEXT_RETIRED,
        );
        $inspection = (new MemberSensitiveDataInspector($crypto))->inspect([
            'id' => 'member-1',
            'identity_number' => null,
            'identity_number_enc' => $crypto->encrypt('3201234567890001'),
            'identity_number_key_version' => 'v2',
            'identity_number_bidx' => $crypto->blindIndexForVersion('identity_number', '3201234567890001', 'v2'),
            'identity_number_bidx_version' => 'v2',
            'identity_number_migrated_at' => '2026-07-14 00:00:00',
        ]);

        $this->assertNotContains(
            'missing_plaintext_compatibility_copy',
            $inspection['fields']['identity_number']['issues'],
        );
    }

    private function crypto(): PiiCryptoService
    {
        return new PiiCryptoService(
            ['v1' => $this->key('encryption-v1'), 'v2' => $this->key('encryption-v2')],
            'v2',
            ['v1' => $this->key('blind-index-v1'), 'v2' => $this->key('blind-index-v2')],
            'v2',
            null,
            ['v1', 'v2'],
        );
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }
}
