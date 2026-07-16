<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class AuditContext
{
    public const SOURCE_HTTP = 'http';

    public const SOURCE_WEBHOOK = 'webhook';

    public const SOURCE_QUEUE = 'queue';

    public const SOURCE_CLI = 'cli';

    public const SOURCE_SCHEDULER = 'scheduler';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_DOMAIN = 'domain';

    /** @var list<string> */
    public const VALID_SOURCES = [
        self::SOURCE_HTTP,
        self::SOURCE_WEBHOOK,
        self::SOURCE_QUEUE,
        self::SOURCE_CLI,
        self::SOURCE_SCHEDULER,
        self::SOURCE_SYSTEM,
        self::SOURCE_DOMAIN,
    ];

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
    ) {
        if (! in_array($source, self::VALID_SOURCES, true)) {
            throw new InvalidArgumentException("Unsupported audit source [{$source}].");
        }
    }

    public static function fromHttp(Request $request, ?User $actor = null): self
    {
        return self::forActor($actor ?? $request->user(), self::SOURCE_HTTP, $request);
    }

    public static function fromWebhook(Request $request, ?string $organizationId = null, ?string $correlationId = null): self
    {
        $correlationId ??= $request->header('X-Correlation-ID');

        return new self(
            actorId: null,
            actorRoles: [],
            organizationId: $organizationId,
            correlationId: is_string($correlationId) && Str::isUuid($correlationId)
                ? $correlationId
                : (string) Str::uuid(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            source: self::SOURCE_WEBHOOK,
        );
    }

    public static function forQueue(?string $organizationId = null, ?string $correlationId = null): self
    {
        return new self(
            actorId: null,
            actorRoles: [],
            organizationId: $organizationId,
            correlationId: self::normalizeCorrelationId($correlationId),
            ip: null,
            userAgent: null,
            source: self::SOURCE_QUEUE,
        );
    }

    public static function forScheduler(?string $organizationId = null, ?string $correlationId = null): self
    {
        return new self(
            actorId: null,
            actorRoles: [],
            organizationId: $organizationId,
            correlationId: self::normalizeCorrelationId($correlationId),
            ip: null,
            userAgent: null,
            source: self::SOURCE_SCHEDULER,
        );
    }

    public static function forCli(?User $actor = null, ?string $correlationId = null): self
    {
        return self::forActor($actor, self::SOURCE_CLI, correlationId: $correlationId);
    }

    public static function forSystem(?string $organizationId = null, ?string $correlationId = null): self
    {
        return new self(
            actorId: null,
            actorRoles: [],
            organizationId: $organizationId,
            correlationId: self::normalizeCorrelationId($correlationId),
            ip: null,
            userAgent: null,
            source: self::SOURCE_SYSTEM,
        );
    }

    public static function forActor(
        ?User $actor,
        string $source = self::SOURCE_DOMAIN,
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
            : (app()->runningInConsole() ? self::forCli(auth()->user()) : self::forSystem());
    }

    private static function normalizeCorrelationId(?string $correlationId): string
    {
        return is_string($correlationId) && Str::isUuid($correlationId)
            ? $correlationId
            : (string) Str::uuid();
    }
}
