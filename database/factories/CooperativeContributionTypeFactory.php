<?php

namespace Database\Factories;

use App\Models\CooperativeContributionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeContributionType>
 */
class CooperativeContributionTypeFactory extends Factory
{
    protected $model = CooperativeContributionType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => 'Simpanan '.fake()->word(),
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ];
    }

    public function pokok(int $amount = 200000): static
    {
        return $this->state(fn () => [
            'code' => 'POKOK-'.fake()->unique()->numerify('###'),
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => $amount,
            'frequency' => 'ONCE',
        ]);
    }

    public function wajib(int $amount = 50000): static
    {
        return $this->state(fn () => [
            'code' => 'WAJIB-'.fake()->unique()->numerify('###'),
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => $amount,
            'frequency' => 'MONTHLY',
        ]);
    }
}
