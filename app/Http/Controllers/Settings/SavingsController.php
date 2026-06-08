<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSavingsSettingsRequest;
use App\Models\CooperativeContributionType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function edit(): Response
    {
        $this->authorizeSavingsSettings();

        return Inertia::render('settings/Savings', [
            'settings' => [
                'wajib' => $this->ensureContributionType('WAJIB'),
                'pokok' => $this->ensureContributionType('POKOK'),
            ],
        ]);
    }

    public function update(UpdateSavingsSettingsRequest $request): RedirectResponse
    {
        $this->authorizeSavingsSettings();

        $wajib = $this->ensureContributionType('WAJIB');
        $pokok = $this->ensureContributionType('POKOK');

        $wajib->update([
            'default_amount' => $request->validated('wajib_default_amount'),
        ]);

        $pokok->update([
            'default_amount' => $request->validated('pokok_default_amount'),
        ]);

        return to_route('settings.savings.edit')->with('success', 'Pengaturan simpanan berhasil diperbarui.');
    }

    private function authorizeSavingsSettings(): void
    {
        abort_unless(request()->user()?->can('manage_cooperative_dues'), 403);
    }

    private function ensureContributionType(string $code): CooperativeContributionType
    {
        return CooperativeContributionType::query()->firstOrCreate(
            ['code' => $code],
            $this->contributionTypeDefaults($code),
        );
    }

    /**
     * @return array{name: string, category: string, default_amount: int, frequency: string, is_active: bool}
     */
    private function contributionTypeDefaults(string $code): array
    {
        return match ($code) {
            'WAJIB' => [
                'name' => 'Simpanan Wajib',
                'category' => 'WAJIB',
                'default_amount' => 100000,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ],
            'POKOK' => [
                'name' => 'Simpanan Pokok',
                'category' => 'POKOK',
                'default_amount' => 200000,
                'frequency' => 'ONCE',
                'is_active' => true,
            ],
            default => [
                'name' => $code,
                'category' => $code,
                'default_amount' => 0,
                'frequency' => 'ADHOC',
                'is_active' => true,
            ],
        };
    }
}
