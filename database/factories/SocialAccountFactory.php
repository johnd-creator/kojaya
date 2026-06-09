<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_id' => (string) fake()->unique()->numberBetween(100000, 999999999),
            'provider_email' => fake()->unique()->safeEmail(),
            'provider_name' => fake()->name(),
            'provider_avatar' => 'https://example.com/avatar.png',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'linked_at' => now(),
            'last_login_at' => now(),
        ];
    }

    public function google(): static
    {
        return $this->state(fn () => [
            'provider' => 'google',
            'provider_id' => (string) fake()->unique()->numerify('1##########'),
        ]);
    }
}
