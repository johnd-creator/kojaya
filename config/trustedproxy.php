<?php

$trustedProxies = trim((string) env('TRUSTED_PROXIES', '127.0.0.1,::1'));

return [
    'proxies' => match (true) {
        $trustedProxies === '' => null,
        in_array($trustedProxies, ['*', '**'], true) => $trustedProxies,
        default => array_values(array_filter(
            array_map('trim', explode(',', $trustedProxies)),
            static fn (string $proxy): bool => $proxy !== '',
        )),
    },
];
