<?php

namespace Tests\Unit;

use Illuminate\Auth\GenericUser;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class TokenGuardCredentialTypeTest extends TestCase
{
    public function test_non_string_query_and_body_tokens_never_reach_user_lookup(): void
    {
        foreach ([1, true, ['synthetic']] as $token) {
            foreach (['GET', 'POST'] as $method) {
                $provider = $this->createMock(UserProvider::class);
                $provider->expects(self::never())->method('retrieveByCredentials');
                $guard = new TokenGuard($provider, Request::create('/fixture', $method, ['api_token' => $token]));
                self::assertNull($guard->user());
            }
        }
    }

    public function test_non_string_validate_credentials_never_reach_user_lookup(): void
    {
        $provider = $this->createMock(UserProvider::class);
        $provider->expects(self::never())->method('retrieveByCredentials');
        $guard = new TokenGuard($provider, Request::create('/fixture'));
        foreach ([1, true, ['synthetic'], null] as $token) {
            self::assertFalse($guard->validate(['api_token' => $token]));
        }
    }

    public function test_string_bearer_still_looks_up_exact_synthetic_credential(): void
    {
        $user = new GenericUser(['id' => 7]);
        $provider = $this->createMock(UserProvider::class);
        $provider->expects(self::once())->method('retrieveByCredentials')->with(['api_token' => 'synthetic-only'])->willReturn($user);
        $request = Request::create('/fixture');
        $request->headers->set('Authorization', 'Bearer synthetic-only');
        self::assertSame($user, (new TokenGuard($provider, $request))->user());
    }
}
