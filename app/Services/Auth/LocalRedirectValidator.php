<?php

namespace App\Services\Auth;

class LocalRedirectValidator
{
    /**
     * Validate and normalize a destination URL to a safe relative application path.
     *
     * Returns null if the URL is external, malformed, contains ambiguous/dangerous
     * network-path indicators, or mismatches the configured application origin.
     */
    public function toLocalUrl(?string $url, ?string $appUrl = null): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Reject control characters (0x00-0x1F, 0x7F) and URL-encoded CRLF/null bytes
        if (preg_match('/[\x00-\x1F\x7F]/', $url) || preg_match('/%0[0aAdD]/i', $url)) {
            return null;
        }

        // Reject backslashes anywhere to prevent browser slash-normalization bypasses
        if (str_contains($url, '\\')) {
            return null;
        }

        // Relative path input
        if (str_starts_with($url, '/')) {
            return $this->validateSafeRelativePath($url);
        }

        // Absolute URL input - must be same-origin
        $parsed = parse_url($url);
        if ($parsed === false || ! is_array($parsed)) {
            return null;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        // Reject any userinfo (e.g. https://app.kojaya.id@evil.example or https://user:pass@app.kojaya.id)
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return null;
        }

        $host = strtolower($parsed['host'] ?? '');
        if ($host === '') {
            return null;
        }

        if ($appUrl === null) {
            $container = \Illuminate\Container\Container::getInstance();
            $appUrl = ($container !== null && $container->bound('config'))
                ? (string) config('app.url')
                : '';
        }

        $appParsed = parse_url($appUrl);
        if ($appParsed === false || ! is_array($appParsed) || empty($appParsed['scheme']) || empty($appParsed['host'])) {
            return null;
        }

        $appScheme = strtolower($appParsed['scheme']);
        $appHost = strtolower($appParsed['host']);

        // Scheme and host must match exactly
        if ($scheme !== $appScheme || $host !== $appHost) {
            return null;
        }

        // Compare effective ports
        $port = isset($parsed['port']) ? (int) $parsed['port'] : ($scheme === 'https' ? 443 : 80);
        $appPort = isset($appParsed['port']) ? (int) $appParsed['port'] : ($appScheme === 'https' ? 443 : 80);

        if ($port !== $appPort) {
            return null;
        }

        // Extract and normalize to relative path
        $path = $parsed['path'] ?? '/';
        if ($path === '' || ! str_starts_with($path, '/')) {
            $path = '/'.ltrim($path, '/');
        }

        // Preserve legitimate query string
        if (isset($parsed['query']) && $parsed['query'] !== '') {
            $path .= '?'.$parsed['query'];
        }

        // Fragments are intentionally omitted for server-side redirects

        return $this->validateSafeRelativePath($path);
    }

    /**
     * Determine if a destination URL is safe and local.
     */
    public function isSafeLocal(?string $url, ?string $appUrl = null): bool
    {
        return $this->toLocalUrl($url, $appUrl) !== null;
    }

    /**
     * Validate that a relative path is strictly internal, begins with exactly one slash,
     * and contains no scheme, host, or protocol-relative sequences.
     */
    private function validateSafeRelativePath(string $path): ?string
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        if (str_contains($path, '\\')) {
            return null;
        }

        $parsed = parse_url($path);
        if ($parsed === false || ! is_array($parsed)) {
            return null;
        }

        if (isset($parsed['scheme']) || isset($parsed['host']) || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['port'])) {
            return null;
        }

        $parsedPath = $parsed['path'] ?? '';
        if (! str_starts_with($parsedPath, '/') || str_starts_with($parsedPath, '//')) {
            return null;
        }

        $normalized = $parsedPath;
        if (isset($parsed['query']) && $parsed['query'] !== '') {
            $normalized .= '?'.$parsed['query'];
        }

        return $normalized;
    }
}
