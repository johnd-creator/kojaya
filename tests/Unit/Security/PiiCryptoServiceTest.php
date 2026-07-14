<?php

namespace Tests\Unit\Security;

use App\Exceptions\PiiDecryptionException;
use App\Services\Security\PiiCryptoService;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PiiCryptoServiceTest extends TestCase
{
    public function test_index_normalization_is_shared_across_formatting_variants(): void
    {
        $service = $this->service();

        $this->assertSame(
            $service->blindIndex('identity_number', '3201-2345 6789 0001'),
            $service->blindIndex('identity_number', '3201234567890001'),
        );
        $this->assertSame(
            $service->blindIndex('npwp', '12.345.678.9-012.000'),
            $service->blindIndex('npwp', '123456789012000'),
        );
        $this->assertSame(
            $service->blindIndex('no_rekening', '1234 5678'),
            $service->blindIndex('no_rekening', '12345678'),
        );
    }

    public function test_encrypt_and_decrypt_use_a_versioned_envelope(): void
    {
        $service = $this->service();
        $ciphertext = $service->encrypt('12.345.678.9-012.000');

        $this->assertNotNull($ciphertext);
        $this->assertStringContainsString('"version":"v1"', (string) base64_decode((string) $ciphertext));
        $this->assertSame(
            '12.345.678.9-012.000',
            $service->decrypt($ciphertext, 'npwp', 'member-1'),
        );
    }

    public function test_wrong_key_is_observable_and_never_silently_falls_back(): void
    {
        $ciphertext = $this->service()->encrypt('3201234567890001');
        $wrongKeyService = new PiiCryptoService(
            ['v1' => $this->key('x')],
            'v1',
            ['v1' => $this->key('i')],
            'v1',
        );

        $this->expectException(PiiDecryptionException::class);

        $wrongKeyService->decrypt($ciphertext, 'identity_number', 'member-1');
    }

    public function test_current_versions_must_exist_and_keys_must_be_distinct(): void
    {
        $this->expectException(RuntimeException::class);

        new PiiCryptoService(
            ['v1' => $this->key('same')],
            'v2',
            ['v1' => $this->key('index')],
            'v1',
        );
    }

    public function test_rotating_versions_changes_the_blind_index_and_supports_decryption(): void
    {
        $service = new PiiCryptoService(
            ['v1' => $this->key('encryption-v1'), 'v2' => $this->key('encryption-v2')],
            'v2',
            ['v1' => $this->key('blind-index-v1'), 'v2' => $this->key('blind-index-v2')],
            'v2',
        );

        $this->assertNotSame(
            $service->blindIndex('identity_number', '3201234567890001', 'v1'),
            $service->blindIndex('identity_number', '3201234567890001', 'v2'),
        );
        $ciphertext = $service->encrypt('3201234567890001');

        $this->assertSame('3201234567890001', $service->decrypt($ciphertext, 'identity_number', 'member-1'));
    }

    public function test_previous_unversioned_ciphertext_requires_an_explicit_legacy_key(): void
    {
        $legacyKey = $this->key('legacy-encryption');
        $legacyEncrypter = new Encrypter(
            base64_decode(substr($legacyKey, 7), true),
            'aes-256-cbc',
        );
        $legacyCiphertext = $legacyEncrypter->encryptString('3201234567890001');

        $service = new PiiCryptoService(
            ['v1' => $this->key('encryption')],
            'v1',
            ['v1' => $this->key('blind-index')],
            'v1',
            $legacyKey,
        );

        $this->assertSame(
            '3201234567890001',
            $service->decrypt($legacyCiphertext, 'identity_number', 'member-1'),
        );
    }

    private function service(): PiiCryptoService
    {
        return new PiiCryptoService(
            ['v1' => $this->key('encryption')],
            'v1',
            ['v1' => $this->key('blind-index')],
            'v1',
        );
    }

    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(hash('sha256', $seed, true));
    }
}
