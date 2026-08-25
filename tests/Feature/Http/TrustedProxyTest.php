<?php

namespace Tests\Feature\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_https_forwarded_headers_are_used_for_a_configured_proxy(): void
    {
        config()->set('trustedproxy.proxies', ['127.0.0.1']);

        Route::get('/__test/trusted-proxy', static function (Request $request): JsonResponse {
            return response()->json([
                'secure' => $request->secure(),
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'url' => url('/probe'),
            ]);
        });

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'internal.test',
            'SERVER_PORT' => '80',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
            'HTTP_X_FORWARDED_HOST' => 'qa.kojaya.id',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/__test/trusted-proxy');

        $response->assertOk()->assertJson([
            'secure' => true,
            'scheme' => 'https',
            'host' => 'qa.kojaya.id',
            'url' => 'https://qa.kojaya.id/probe',
        ]);

        Route::get('/__test/trusted-proxy-redirect', static function (): RedirectResponse {
            return redirect()->route('login');
        });

        $redirect = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'internal.test',
            'SERVER_PORT' => '80',
            'HTTP_X_FORWARDED_HOST' => 'qa.kojaya.id',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/__test/trusted-proxy-redirect');

        $redirect->assertRedirect('https://qa.kojaya.id/login');
    }

    public function test_forwarded_headers_from_an_untrusted_source_are_ignored(): void
    {
        config()->set('trustedproxy.proxies', ['127.0.0.1']);

        Route::get('/__test/untrusted-proxy', static function (Request $request): JsonResponse {
            return response()->json([
                'secure' => $request->secure(),
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'url' => url('/probe'),
            ]);
        });

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.10',
            'HTTP_HOST' => 'internal.test',
            'SERVER_PORT' => '80',
            'HTTP_X_FORWARDED_HOST' => 'qa.kojaya.id',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/__test/untrusted-proxy');

        $response->assertOk()->assertJson([
            'secure' => false,
            'scheme' => 'http',
            'host' => 'localhost',
            'url' => 'http://localhost:8000/probe',
        ]);
    }
}
