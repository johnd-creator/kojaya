<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Password');
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request): void {
            $request->user()->update(['password' => $request->password]);
            app(\App\Services\Auth\MemberSessionService::class)->revokeForUser($request->user(), 'password_changed', $request->user());
        });

        return back();
    }
}
