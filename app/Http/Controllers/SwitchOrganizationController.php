<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchOrganizationRequest;
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
    public function switch(SwitchOrganizationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Store active organization in session
        // null = consolidated view (all organizations)
        session(['active_organization_id' => $validated['organization_id'] ?? null]);

        return back()
            ->with('success', 'Organization context updated successfully.');
    }
}
