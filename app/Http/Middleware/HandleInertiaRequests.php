<?php

namespace App\Http\Middleware;

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
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => fn () => $request->user()?->getRoleNames() ?? [],
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name')->values() ?? [],
            ],
            'appearance' => $request->cookie('appearance') ?? 'system',
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'active_organization' => fn () => session('active_organization_id')
                ? \App\Models\Organization::find(session('active_organization_id'))
                : null,
            'user_organizations' => fn () => $request->user() && $request->user()->hasRole('System Admin')
                ? \App\Models\Organization::orderBy('name')->get()
                : ($request->user() && $request->user()->organization_id ? \App\Models\Organization::where('id', $request->user()->organization_id)->get() : []),
        ];
    }
}
