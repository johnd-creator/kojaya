<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
                $phase = (string) config('security.ability_cutover_phase', 'instrument');
                $emergencyEnabled = (bool) config('security.legacy_ability_fallback_enabled', false);
                $expiry = config('security.legacy_ability_fallback_expires_at');
                $expiryIsFuture = $this->isFutureExpiry($expiry);

                if ($phase === 'remove' && ! ($emergencyEnabled && $expiryIsFuture)) {
                    continue;
                }

                $graceUntil = config('security.legacy_token_grace_until');
                if (in_array($phase, ['rotate', 'deprecate'], true)
                    && is_string($graceUntil)
                    && trim($graceUntil) !== ''
                    && ! $this->isFutureExpiry($graceUntil)) {
                    continue;
                }

                $token = $request->user()->currentAccessToken();
                $app = is_object($token) ? ($token->token_app ?? 'legacy') : 'legacy';
                $version = is_object($token) ? ($token->token_version ?? 'legacy') : 'legacy';
                Cache::increment('auth.legacy_ability.'.sha1($request->route()?->getName() ?? $request->path()).'.'.$app.'.'.$version);
                $this->audit->log('cooperative.legacy_ability.used', 'cooperative.api', null, [
                    'new' => [
                        'ability' => $ability,
                        'token_app' => $app,
                        'token_version' => $version,
                        'route' => $request->route()?->getName() ?? $request->path(),
                    ],
                    'reason' => $phase === 'remove'
                        ? 'Emergency legacy cooperative ability fallback used before its configured expiry.'
                        : 'Legacy cooperative ability accepted during granular ability migration.',
                ]);
            }

            $response = $next($request);
            if (str_starts_with($ability, 'cooperative:') && config('security.ability_cutover_phase') === 'deprecate') {
                $response->headers->set('Deprecation', 'true');
                if ($expiry = config('security.legacy_ability_fallback_expires_at')) {
                    $response->headers->set('Sunset', (string) $expiry);
                }
            }

            return $response;
        }

        throw new MissingAbilityException($abilities);
    }

    private function isFutureExpiry(mixed $expiry): bool
    {
        if (! is_string($expiry) || trim($expiry) === '') {
            return false;
        }

        try {
            return Carbon::parse($expiry)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }
}
