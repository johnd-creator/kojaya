<?php

namespace Tests\Support;

use RuntimeException;

trait CreatesTestRsaJwk
{
    private const TEST_PRIVATE_KEY_BASE64 = 'LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JSUV2Z0lCQURBTkJna3Foa2lHOXcwQkFRRUZBQVNDQktnd2dnU2tBZ0VBQW9JQkFRQ3FLNWMySHFxVGx1VGsKK1V3VUNqNU8yNVJpVXdLYi9KQXdLL0FMeTJnb0FmYnZjZXZHcURYNVVDaWs3UWFSK0tjRndqWnc0ZWx6bU1ULwpaUExlc3JEckRXNm9wUlRpSG5KeVlCQ3EwSnZYQXdXS2sxZWhXdGFZTEVERE81c3Z6VGM3RnlJK29nRUdnZmZXCmE5bm9vRnAya1g4VjJEbU1VK2w4ejJoN01DNTdFOGp4em1EYkZ3bW9jZTFsWHBDYW1CRXZvOHBGTXpiM1dzWm0Kc3h1dERSeWcvODJFMjZ3Nkpkb1U3ajc4VUJnWDVsTkkwNUpQYUtiQ3haSHhDdENDdjVrSHo0SmRLcGcxMEpOVwpiMFI5KzFoKzhDZXpJYUFXZDRlcGF1bzN2SWVKUTdDcWYwbFJVNUVjNVNzZjQ2WURmQ2FMOXhCTnROelkyamRZCnRDVGVPOVUzQWdNQkFBRUNnZ0VBSE5OY2ZuVHNaU3JhNURTY29BcHFLcGFFaUxGU0VGVlVvV3hYOGMrSGVidDMKZG5FZ1JOc0twWXhnRGl2K2dHeVQ4bVJITDVEOUtERTVNYUFLaWhIUDZVa3h0UXlkd0gzeTJoQU8wcmlhcFczawpHdWpCbjlvUTN0OGtLMDRtQ0E5bHF6Tk1nRHFXSE9HWU4zQzJqWExZVUJ4dVlDZHIvQ3Jjc3VFNWJDQ3B4dnc0Ckc0ZnNkYlVObFhsRDI2RTJVb0JhVmMvR1JkU1VmbXM5cjNYUzVyMk1ZdC9RSFpwbktIMVdKTVh3WEQvbGxYcVoKL0xUelJTbHptSDFWbjRtdkFVK3drZ3Z3bkllZ1RaRUhuUWw5S05CMlZweEJ4UHhyYXRaZkMyNWxaY1lGNW1mcwpLQlRKTzh0OXR1YWFzM2hWYjA5UjdiQjI4T0N4bEltNUxuQXBMd0R0N1FLQmdRRHZsbHduU3EvOHE4c2dvaTl3CkJYdTlTY1kyai9od2FiZTZlTkVRak00TGdRQzAyMTRuWjhmZnRnMm1EL3B0WWh0VVJJVC9zMytBcVdoR09aNy8KNyt2ZUk1ek1EdVJZcC9iWGdnQlFJK2QyWi9pQy8yM3A2aVNucmtNN092YUVUQXdRZkJyR1h1MUsrd254VytWMQpsYUhzekVwcm9hUGtNK1RoRUwxNDdCUDFHd0tCZ1FDMTA5MVF3bVZ1Q0xvWDU5djhXYkZTWWptdExOdEErb0pQCmcyZEplb1FEWWc4Q0I5bm5DVnFKL0VWbW5SSlkzNHpjcG9BaENVUjExSWc0SVVoRVQ4Q3lZSGZJMkw1d3lGdUMKelJBN01sSExySHhoRXJSaDhOVjlPejF1MnBEZDgrempFUWJ3UXJ3MDViNUZnMDhZNGJBdWlwZkZYL0U2NzdZeApuNlFnTXVmT0ZRS0JnUURlQ0V0SXdvZUxzcTJoaFl4TzFWWVNXczlOZTJqeXpKWlBRbTRGVjJnUDh6SnphU1M5Cmdna1NRb1l0a25zemFZc0lNaVBMVUU3bUxwa0xFNVZOZk12cTVyZ0Z4L3RJU2dpUk9kSU9jdWVyckxqNnRicm4KMzJ5dTFPbExkOTVEUnJLYmlGZkw1T2NsNkxZVExtWGM3Wm5OZUptelcyNG1LdzErb21QbEwydGpad0tCZ0JzaQovSlBSLzN0Vm1CaDdSU3k3WWlpT2VsY3JLNm5kK1ZiT29McXBxMHdwOVYxek9JVXZzekNHMHdER2puZHZIY0hNCm83REtoa29qcHhUaGVyeWZQbjRnd1ovYklVa0p6Z2FPZms2bmF1ZS8zV3hMYzFwdXJCNGRta1NTSUM3UCtkbkYKcjhocDNWYnp0dHIvQnU0S3VOV3BYNDlZaTFNbGRZYWdjY2xRYjZJbEFvR0JBS1F5Y1NQWllkUlorUHBNSk55QgozdmY5VGl2LzZKQVdWdEs3NGtjTndkeERHUmZOVzNTQnUzVEh4eWFLWHU3MnU1UmQ4d2hheGt5d2FzcWxZRlZFCmRIMThUcnZDcUw1TjB6ZUJaWUVvc3F1NEMrenBpU1REMXdHVWQxeTZlbnAveU9YWTZ2VDNRTEpKSXM1WWlqUlMKL0dPYTVsYnpnUk9DUkJMNVBNVkhZdW1pCi0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0K';

    /**
     * @return array{private_key: string, jwk: array<string, string>}
     */
    protected function fakeRsaJwk(): array
    {
        $privateKey = base64_decode(self::TEST_PRIVATE_KEY_BASE64, true);
        $resource = is_string($privateKey) ? openssl_pkey_get_private($privateKey) : false;

        if ($resource === false || ! is_string($privateKey)) {
            throw new RuntimeException('Unable to create the test RSA key.');
        }

        $details = openssl_pkey_get_details($resource);

        if (! is_array($details) || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new RuntimeException('Unable to inspect the test RSA key.');
        }

        return [
            'private_key' => $privateKey,
            'jwk' => [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => 'test-kid',
                'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
            ],
        ];
    }
}
