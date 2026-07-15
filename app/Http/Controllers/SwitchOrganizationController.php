<?php

namespace App\Http\Controllers;

use App\Enums\PermissionEnum;
use App\Http\Requests\SwitchOrganizationRequest;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Http\RedirectResponse;

class SwitchOrganizationController extends Controller
{
    /**
     * Switch the active organization context for the current user.
     *
     * This endpoint stores the selected organization_id in session,
     * allowing users with appropriate permissions to view data from
     * different organizations or see consolidated data across all.
     */
    public function switch(
        SwitchOrganizationRequest $request,
        OrganizationScopeService $scopeService,
    ): RedirectResponse {
        $validated = $request->validated();
        $visibility = $scopeService->visibilityFor(
            $request->user(),
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        );
        $organizationId = $validated['organization_id'] ?? null;

        if ($organizationId === null) {
            abort_unless($visibility->global, 403, 'Only global cooperative users may select consolidated view.');
        } else {
            $organizationId = $scopeService->assertOrganizationIdentifier($organizationId);
            abort_unless(
                $visibility->global || (string) $visibility->organizationId === $organizationId,
                403,
                'The selected organization is outside the user scope.',
            );
        }

        session(['active_organization_id' => $organizationId]);

        return back()
            ->with('success', 'Organization context updated successfully.');
    }
}
