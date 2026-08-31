<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionOrTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Sanctum\Exceptions\MissingAbilityException
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        $currentToken = $user->currentAccessToken();

        // 1. If authenticated via bearer PersonalAccessToken, enforce scoped abilities
        if ($currentToken instanceof PersonalAccessToken) {
            $tokenAbilities = (array) ($currentToken->abilities ?? []);

            // If authenticated via bearer token, wildcard '*' is not permitted
            if (in_array('*', $tokenAbilities, true) || $currentToken->can('*')) {
                throw new MissingAbilityException($abilities, 'Wildcard token abilities are not permitted.');
            }

            foreach ($abilities as $ability) {
                if ($currentToken->can($ability)) {
                    return $next($request);
                }
            }

            throw new MissingAbilityException($abilities);
        }

        // 2. If authenticated via Sanctum TransientToken (web session), allow access to session-safe endpoint
        if ($currentToken instanceof \Laravel\Sanctum\TransientToken) {
            return $next($request);
        }

        // 3. Null or unknown token implementation: fail closed
        throw new MissingAbilityException($abilities, 'This endpoint requires a valid session or API token.');
    }
}
