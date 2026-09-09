<?php

namespace Tests\Unit\Auth;

use App\Services\Auth\LocalRedirectValidator;
use Tests\TestCase;

class LocalRedirectValidatorTest extends TestCase
{
    private LocalRedirectValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LocalRedirectValidator;
    }

    public function test_rejects_null_and_empty_strings(): void
    {
        $this->assertNull($this->validator->toLocalUrl(null));
        $this->assertNull($this->validator->toLocalUrl(''));
        $this->assertNull($this->validator->toLocalUrl('   '));
    }

    public function test_rejects_external_absolute_urls(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertNull($this->validator->toLocalUrl('https://evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('http://evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://evil.example/phishing', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://evil.example/app.kojaya.id', $appUrl));
    }

    public function test_rejects_protocol_relative_urls(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertNull($this->validator->toLocalUrl('//evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('//evil.example/phishing', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('//app.kojaya.id/phishing', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('///evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('////evil.example', $appUrl));
    }

    public function test_rejects_lookalike_hostnames(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id.evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id.evil.example/dashboard', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://notkojaya.id', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://sub.app.kojaya.id', $appUrl));
    }

    public function test_rejects_userinfo_hostname_confusion(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id@evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id@evil.example/dashboard', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://user:password@evil.example', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://evil.example@app.kojaya.id', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://user:pass@app.kojaya.id/dashboard', $appUrl));
    }

    public function test_rejects_scheme_mismatch(): void
    {
        $appUrl = 'https://app.kojaya.id';

        // HTTPS app origin rejects HTTP input
        $this->assertNull($this->validator->toLocalUrl('http://app.kojaya.id/dashboard', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('http://app.kojaya.id', $appUrl));
    }

    public function test_rejects_port_mismatch(): void
    {
        $appUrl = 'https://app.kojaya.id';

        // Default HTTPS port is 443; port 444 or 8080 must be rejected
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id:444/member', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id:8080/member', $appUrl));
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id:80/member', $appUrl));

        // When app origin has custom port, different ports must be rejected
        $customAppUrl = 'http://localhost:8000';
        $this->assertNull($this->validator->toLocalUrl('http://localhost:9000/dashboard', $customAppUrl));
        $this->assertNull($this->validator->toLocalUrl('http://localhost/dashboard', $customAppUrl));
        $this->assertSame('/dashboard', $this->validator->toLocalUrl('http://localhost:8000/dashboard', $customAppUrl));
    }

    public function test_rejects_non_http_schemes(): void
    {
        $this->assertNull($this->validator->toLocalUrl('javascript:alert(1)'));
        $this->assertNull($this->validator->toLocalUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertNull($this->validator->toLocalUrl('ftp://evil.example/file'));
        $this->assertNull($this->validator->toLocalUrl('file:///etc/passwd'));
        $this->assertNull($this->validator->toLocalUrl('mailto:user@example.com'));
    }

    public function test_rejects_backslash_and_network_path_tricks(): void
    {
        $this->assertNull($this->validator->toLocalUrl('\\evil.example'));
        $this->assertNull($this->validator->toLocalUrl('\\\\evil.example'));
        $this->assertNull($this->validator->toLocalUrl('/\\evil.example'));
        $this->assertNull($this->validator->toLocalUrl('\\/evil.example'));
        $this->assertNull($this->validator->toLocalUrl('/foo\\bar'));
        $this->assertNull($this->validator->toLocalUrl('https://app.kojaya.id\\evil.example', 'https://app.kojaya.id'));
    }

    public function test_rejects_control_characters_and_crlf_injection(): void
    {
        $this->assertNull($this->validator->toLocalUrl("/member\r\nLocation: https://evil.example"));
        $this->assertNull($this->validator->toLocalUrl("/member\nLocation: https://evil.example"));
        $this->assertNull($this->validator->toLocalUrl("/member\0phishing"));
        $this->assertNull($this->validator->toLocalUrl('/member%0d%0aLocation:evil'));
        $this->assertNull($this->validator->toLocalUrl('/member%00evil'));
    }

    public function test_rejects_malformed_urls(): void
    {
        $this->assertNull($this->validator->toLocalUrl('http:///'));
        $this->assertNull($this->validator->toLocalUrl('http://:80'));
        $this->assertNull($this->validator->toLocalUrl('http://'));
        $this->assertNull($this->validator->toLocalUrl('https://'));
        $this->assertNull($this->validator->toLocalUrl('https://[invalid'));
        $this->assertNull($this->validator->toLocalUrl('dashboard')); // missing leading slash
    }

    public function test_allows_and_preserves_valid_relative_paths(): void
    {
        $this->assertSame('/', $this->validator->toLocalUrl('/'));
        $this->assertSame('/dashboard', $this->validator->toLocalUrl('/dashboard'));
        $this->assertSame('/member', $this->validator->toLocalUrl('/member'));
        $this->assertSame('/member/profile', $this->validator->toLocalUrl('/member/profile'));
        $this->assertSame('/cooperative/members', $this->validator->toLocalUrl('/cooperative/members'));
        $this->assertSame('/settings?tab=google', $this->validator->toLocalUrl('/settings?tab=google'));
        $this->assertSame('/search?email=user@example.com', $this->validator->toLocalUrl('/search?email=user@example.com'));
    }

    public function test_allows_and_normalizes_same_origin_absolute_urls(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertSame(
            '/member/profile?tab=security',
            $this->validator->toLocalUrl('https://app.kojaya.id/member/profile?tab=security', $appUrl)
        );
        $this->assertSame(
            '/dashboard',
            $this->validator->toLocalUrl('https://app.kojaya.id/dashboard', $appUrl)
        );
        $this->assertSame(
            '/member/profile',
            $this->validator->toLocalUrl('https://app.kojaya.id:443/member/profile', $appUrl)
        );
        $this->assertSame(
            '/',
            $this->validator->toLocalUrl('https://app.kojaya.id', $appUrl)
        );
        $this->assertSame(
            '/',
            $this->validator->toLocalUrl('https://app.kojaya.id/', $appUrl)
        );
    }

    public function test_omits_fragments_from_normalized_destination(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertSame(
            '/member/profile',
            $this->validator->toLocalUrl('/member/profile#security-tab', $appUrl)
        );
        $this->assertSame(
            '/member/profile?tab=security',
            $this->validator->toLocalUrl('/member/profile?tab=security#section', $appUrl)
        );
        $this->assertSame(
            '/member/profile?tab=security',
            $this->validator->toLocalUrl('https://app.kojaya.id/member/profile?tab=security#section', $appUrl)
        );
    }

    public function test_is_safe_local_helper(): void
    {
        $appUrl = 'https://app.kojaya.id';

        $this->assertTrue($this->validator->isSafeLocal('/dashboard', $appUrl));
        $this->assertTrue($this->validator->isSafeLocal('https://app.kojaya.id/dashboard', $appUrl));
        $this->assertFalse($this->validator->isSafeLocal('https://evil.example', $appUrl));
        $this->assertFalse($this->validator->isSafeLocal('//evil.example', $appUrl));
    }
}
