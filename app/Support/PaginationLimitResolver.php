<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PaginationLimitResolver
{
    public const DEFAULT = 15;

    public const MINIMUM = 1;

    public const MAXIMUM = 50;

    public const ADMIN_MAXIMUM = 100;

    public function resolve(
        Request $request,
        string $parameter = 'per_page',
        int $default = self::DEFAULT,
        int $maximum = self::MAXIMUM,
    ): int {
        $maximum = min(max(self::MINIMUM, $maximum), self::ADMIN_MAXIMUM);
        $default = min(max($default, self::MINIMUM), $maximum);
        $raw = $request->query($parameter);

        if ($raw === null || $raw === '') {
            return $default;
        }

        if (is_array($raw) || filter_var($raw, FILTER_VALIDATE_INT) === false) {
            return $default;
        }

        return min(max((int) $raw, self::MINIMUM), $maximum);
    }
}
