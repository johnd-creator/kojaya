<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class AuditContext
{
    /**
     * @param  list<string>  $actorRoles
     */
    public function __construct(
        public string|int|null $actorId,
        public array $actorRoles,
        public string|int|null $organizationId,
        public string $correlationId,
        public ?string $ip,
        public ?string $userAgent,
        public string $source,
    ) {}

    public static function fromHttp(Request $request, ?User $actor = null): self
    {
        return self::forActor($actor ?? $request->user(), 'http', $request);
    }

    public static function forActor(
        ?User $actor,
        string $source = 'domain',
        ?Request $request = null,
        ?string $correlationId = null,
    ): self {
        $correlationId ??= $request?->header('X-Correlation-ID');

        return new self(
            actorId: $actor?->getKey(),
            actorRoles: $actor?->getRoleNames()->values()->all() ?? [],
            organizationId: $actor?->organization_id,
            correlationId: is_string($correlationId) && Str::isUuid($correlationId)
                ? $correlationId
                : (string) Str::uuid(),
            ip: $request?->ip(),
            userAgent: $request?->userAgent(),
            source: $source,
        );
    }

    public static function fromCurrentRequest(): self
    {
        $request = app()->bound('request') ? app('request') : null;

        return $request instanceof Request
            ? self::fromHttp($request)
            : self::forActor(auth()->user(), app()->runningInConsole() ? 'cli' : 'system');
    }
}
