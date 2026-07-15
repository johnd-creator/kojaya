<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Security\UserRoleManagementService;
use App\Support\AuditContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePermission('manage_users');

        $users = User::with(['roles', 'organization', 'cooperativeMember'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('cooperativeMember', function ($query) use ($search): void {
                            $query->where('member_no', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('User/Index', [
            'users' => $users,
            'roles' => Role::all(),
            'organizations' => Organization::all(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(StoreUserRequest $request, UserRoleManagementService $service)
    {
        $this->authorizePermission('manage_users');

        $service->createUserWithAudit(
            $request->validated(),
            $request->user(),
            AuditContext::fromHttp($request, $request->user()),
        );

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user, UserRoleManagementService $service)
    {
        $this->authorizePermission('manage_users');

        $service->updateUserWithAudit(
            $user,
            $request->validated(),
            $request->user(),
            AuditContext::fromHttp($request, $request->user()),
        );

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizePermission('manage_users');

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
