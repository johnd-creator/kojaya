<?php

namespace App\Concerns;

use App\Support\PaginationLimitResolver;
use Illuminate\Http\Request;

trait ResolvesApiPageSize
{
    protected function apiPageSize(Request $request, int $default = 15, int $maximum = 50): int
    {
        return app(PaginationLimitResolver::class)->resolve($request, 'per_page', $default, $maximum);
    }
}
