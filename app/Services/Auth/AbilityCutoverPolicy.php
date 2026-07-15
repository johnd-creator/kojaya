<?php

namespace App\Services\Auth;

use App\Enums\AbilityCutoverPhase;
use Illuminate\Support\Carbon;

class AbilityCutoverPolicy
{
    public function phase(): AbilityCutoverPhase
    {
        return AbilityCutoverPhase::fromConfiguration(config('security.ability_cutover_phase'));
    }

    public function mayIssueLegacyAbilities(): bool
    {
        return $this->phase() === AbilityCutoverPhase::INSTRUMENT;
    }

    public function mayAcceptLegacyAbilities(): bool
    {
        $phase = $this->phase();

        return match ($phase) {
            AbilityCutoverPhase::INSTRUMENT => true,
            AbilityCutoverPhase::ROTATE,
            AbilityCutoverPhase::DEPRECATE => $this->hasFuture('security.legacy_token_grace_until'),
            AbilityCutoverPhase::REMOVE => $this->emergencyFallbackIsActive(),
        };
    }

    public function emergencyFallbackIsActive(): bool
    {
        return (bool) config('security.legacy_ability_fallback_enabled', false)
            && $this->hasFuture('security.legacy_ability_fallback_expires_at');
    }

    public function legacyDeadline(): ?string
    {
        $phase = $this->phase();

        return match ($phase) {
            AbilityCutoverPhase::ROTATE,
            AbilityCutoverPhase::DEPRECATE => (string) config('security.legacy_token_grace_until'),
            AbilityCutoverPhase::REMOVE => (string) config('security.legacy_ability_fallback_expires_at'),
            AbilityCutoverPhase::INSTRUMENT => null,
        };
    }

    private function hasFuture(string $configKey): bool
    {
        $deadline = config($configKey);

        if (! is_string($deadline) || trim($deadline) === '') {
            return false;
        }

        try {
            return Carbon::parse($deadline)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }
}
