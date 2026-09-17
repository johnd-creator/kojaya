<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;

class MemberNumberGenerator
{
    public function getCurrentMaxSequence(): int
    {
        $candidates = CooperativeMember::query()
            ->withTrashed()
            ->where('no_anggota', 'like', 'KOP-%')
            ->pluck('no_anggota');

        $max = $candidates
            ->filter(fn (string $value): bool => (bool) preg_match('/^KOP-\d+$/', $value))
            ->map(fn (string $value): int => (int) substr($value, 4))
            ->max();

        return (int) ($max ?? 0);
    }

    public function format(int $sequence): string
    {
        return 'KOP-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function generate(): string
    {
        return $this->format($this->getCurrentMaxSequence() + 1);
    }

    /**
     * Allocate a batch of consecutive unique member numbers, skipping any excluded numbers
     * (e.g. numbers supplied manually in the same batch).
     *
     * @param  int  $count  Number of members needing generated numbers.
     * @param  list<string>  $exclude  Member numbers to avoid colliding with.
     * @return list<string>
     */
    public function reserveBatch(int $count, array $exclude = []): array
    {
        if ($count <= 0) {
            return [];
        }

        $current = $this->getCurrentMaxSequence();
        $allocated = [];

        while (count($allocated) < $count) {
            $current++;
            $candidate = $this->format($current);

            if (! in_array($candidate, $exclude, true)) {
                $allocated[] = $candidate;
            }
        }

        return $allocated;
    }
}
