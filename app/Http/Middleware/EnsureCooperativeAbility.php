<?php

namespace App\Http\Middleware;

use App\Enums\AbilityCutoverPhase;
use App\Services\AuditLogService;
use App\Services\Auth\AbilityCutoverPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCooperativeAbility
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly AbilityCutoverPolicy $cutover,
    ) {}

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        if (! $request->user()?->currentAccessToken()) {
            throw new \Illuminate\Auth\AuthenticationException;
        }

        $phase = $this->cutover->phase();
        $currentToken = $request->user()->currentAccessToken();
        $tokenAbilities = is_object($currentToken) && is_array($currentToken->abilities ?? null)
            ? $currentToken->abilities
            : [];

        if (in_array('*', $tokenAbilities, true)) {
            throw new MissingAbilityException($abilities);
        }

        foreach ($abilities as $ability) {
            if (! $request->user()->tokenCan($ability)) {
                continue;
            }

            if (str_starts_with($ability, 'cooperative:')) {
                if (! $this->cutover->mayAcceptLegacyAbilities()) {
                    continue;
                }

                $app = is_object($currentToken) ? ($currentToken->token_app ?? 'legacy') : 'legacy';
                $version = is_object($currentToken) ? ($currentToken->token_version ?? 'legacy') : 'legacy';
                $route = $request->route()?->getName() ?? $request->path();
                Cache::increment('auth.legacy_ability.'.sha1($route).'.'.$app.'.'.$version.'.'.$phase->value);
                $this->audit->log('cooperative.legacy_ability.used', 'cooperative.api', null, [
                    'new' => [
                        'ability' => $ability,
                        'token_app' => $app,
                        'token_version' => $version,
                        'phase' => $phase->value,
                        'route' => $route,
                    ],
                    'reason' => $phase === AbilityCutoverPhase::REMOVE
                        ? 'Emergency legacy cooperative ability fallback used before its configured expiry.'
                        : 'Legacy cooperative ability accepted during granular ability migration.',
                ]);
            }

            $response = $next($request);
            if (str_starts_with($ability, 'cooperative:')
                && $phase === AbilityCutoverPhase::DEPRECATE
                && $this->cutover->mayAcceptLegacyAbilities()) {
                $response->headers->set('Deprecation', 'true');
                if ($deadline = $this->cutover->legacyDeadline()) {
                    $response->headers->set('Sunset', $deadline);
                }
            }

            return $response;
        }

        throw new MissingAbilityException($abilities);
    }
}
