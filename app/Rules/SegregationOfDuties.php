<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SegregationOfDuties implements ValidationRule
{
    public function __construct(
        private readonly int $creatorUserId,
        private readonly string $message = 'Creator tidak dapat menyetujui transaksi yang dibuatnya sendiri.',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) $value === $this->creatorUserId) {
            $fail($this->message);
        }
    }
}
