<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCooperativeAbility
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        if (! $request->user()?->currentAccessToken()) {
            throw new \Illuminate\Auth\AuthenticationException;
        }

        foreach ($abilities as $ability) {
            if (! $request->user()->tokenCan($ability)) {
                continue;
            }

            if (str_starts_with($ability, 'cooperative:')) {
                $this->audit->log('cooperative.legacy_ability.used', 'cooperative.api', null, [
                    'new' => [
                        'ability' => $ability,
                        'route' => $request->route()?->getName() ?? $request->path(),
                    ],
                    'reason' => 'Legacy cooperative ability accepted during granular ability migration.',
                ]);
            }

            return $next($request);
        }

        throw new MissingAbilityException($abilities);
    }
}
