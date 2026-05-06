<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    use AuthorizesRequests;

    protected function authorizePermission(string $permission): void
    {
        Gate::authorize($permission);
    }
}
