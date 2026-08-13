<?php

namespace App\Http\Middleware;

use App\Documentation\ContextualHelpRegistry;
use App\Documentation\DocumentationRoleResolver;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\PrimaryRoleResolver;
use App\Services\Cooperative\MemberAccessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->authenticatedUserData($request->user()),
                'roles' => fn () => $request->user()?->getRoleNames() ?? [],
                'primary_role' => fn () => app(PrimaryRoleResolver::class)->resolve($request->user())->value,
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name')->values() ?? [],
                'member_access' => fn () => app(MemberAccessService::class)->for($request->user()?->cooperativeMember),
            ],
            'appearance' => $request->cookie('appearance') ?? 'system',
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'active_organization' => fn () => session('active_organization_id')
                ? $this->organizationData(Organization::find(session('active_organization_id')))
                : null,
            'user_organizations' => fn () => $request->user() && $request->user()->hasRole('System Admin')
                ? Organization::query()->orderBy('name')->get()->map(fn (Organization $organization): array => $this->organizationData($organization))->values()->all()
                : ($request->user() && $request->user()->organization_id
                    ? Organization::query()->whereKey($request->user()->organization_id)->get()->map(fn (Organization $organization): array => $this->organizationData($organization))->values()->all()
                    : []),
            'googleSsoEnabled' => (bool) config('services.google.sso_enabled', false),
            'notifications' => [
                'unreadCount' => fn () => $request->user()
                    ? $request->user()->unreadNotifications()->count()
                    : 0,
            ],
            'contextualHelp' => fn () => $this->resolveContextualHelp($request),
        ];
    }

    /**
     * Compute the contextual help entry for the current route, or
     * `null` if the current request is not authenticated or has no
     * mapping. Lazy: only invoked when the shared prop is read by
     * the frontend, so it never blocks non-documentation routes.
     *
     * @return array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string|null,
     *     screenshot_state: string,
     *     label: string,
     *     article: array{slug: string, title: string, summary: string, category: string, module: string},
     * }|null
     */
    private function resolveContextualHelp(Request $request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $route = $request->route();
        if (! $route) {
            return null;
        }
        $name = $route->getName();
        if (! is_string($name) || $name === '') {
            return null;
        }

        return app(ContextualHelpRegistry::class)->resolveForRequest(
            $name,
            $user,
            app(DocumentationRoleResolver::class),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticatedUserData(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('organization');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->getAttribute('avatar'),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'organization_id' => $user->organization_id,
            'organization' => $this->organizationData($user->organization),
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function organizationData(?Organization $organization): ?array
    {
        if (! $organization) {
            return null;
        }

        return [
            'id' => $organization->id,
            'code' => $organization->code,
            'name' => $organization->name,
            'level' => $organization->level,
            'type' => $organization->type,
            'is_active' => $organization->is_active,
        ];
    }
}
