<?php

namespace App\Concerns;

use Illuminate\Http\Request;

trait ResolvesApiPageSize
{
    protected function apiPageSize(Request $request, int $default = 15, int $maximum = 50): int
    {
        return min(max($request->integer('per_page', $default), 1), $maximum);
    }
}
