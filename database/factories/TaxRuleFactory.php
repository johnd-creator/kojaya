<?php

namespace Database\Factories;

use App\Models\TaxRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxRule>
 */
class TaxRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $default = TaxRule::defaultPph21Ter2024();

        return [
            ...$default,
            'code' => 'PPH21_TER_'.$this->faker->unique()->year(),
            'year' => (int) $this->faker->year(),
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_until' => null,
        ];
    }

    public function effectiveFor(string $date): static
    {
        return $this->state(fn (): array => [
            'effective_from' => $date,
            'effective_until' => null,
        ]);
    }
}
