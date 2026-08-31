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

        // If authenticated via browser session (not PersonalAccessToken), allow access to session-safe identity endpoint
        if (! ($currentToken instanceof PersonalAccessToken)) {
            return $next($request);
        }

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
}
