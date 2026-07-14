<?php

$appKey = (string) env('APP_KEY', '');
if (str_starts_with($appKey, 'base64:')) {
    $appKey = base64_decode(substr($appKey, 7), true) ?: '';
}

$localFallbackKey = static function (string $purpose) use ($appKey): string {
    if ($appKey === '') {
        return '';
    }

    return 'base64:'.base64_encode(hash_hmac('sha256', $purpose, $appKey, true));
};

$isProduction = env('APP_ENV') === 'production';
$encryptionKeyV1 = env('PII_ENCRYPTION_KEY_V1', $isProduction ? '' : $localFallbackKey('kojaya-pii-encryption-v1'));
$legacyEncryptionKey = env('PII_ENCRYPTION_LEGACY_KEY', $isProduction ? '' : ($appKey === '' ? '' : 'base64:'.base64_encode($appKey)));
$blindIndexKeyV1 = env('PII_BLIND_INDEX_KEY_V1', $isProduction ? '' : $localFallbackKey('kojaya-pii-blind-index-v1'));
$encryptionKeys = ['v1' => $encryptionKeyV1];
$blindIndexKeys = ['v1' => $blindIndexKeyV1];

if (($encryptionKeyV2 = env('PII_ENCRYPTION_KEY_V2')) !== null && $encryptionKeyV2 !== '') {
    $encryptionKeys['v2'] = $encryptionKeyV2;
}

if (($blindIndexKeyV2 = env('PII_BLIND_INDEX_KEY_V2')) !== null && $blindIndexKeyV2 !== '') {
    $blindIndexKeys['v2'] = $blindIndexKeyV2;
}

$activeBlindIndexVersions = array_values(array_filter(
    array_map('trim', explode(',', (string) env('PII_BLIND_INDEX_ACTIVE_VERSIONS', 'v1'))),
    static fn (string $version): bool => $version !== '',
));

return [
    'encryption_keys' => $encryptionKeys,
    'encryption_current_version' => env('PII_ENCRYPTION_CURRENT_VERSION', 'v1'),
    'legacy_encryption_key' => $legacyEncryptionKey !== '' ? $legacyEncryptionKey : null,
    'blind_index_keys' => $blindIndexKeys,
    'blind_index_current_version' => env('PII_BLIND_INDEX_CURRENT_VERSION', 'v1'),
    'blind_index_active_versions' => $activeBlindIndexVersions,
    'rollout_phase' => env('PII_ROLLOUT_PHASE', 'dual_write'),
];
