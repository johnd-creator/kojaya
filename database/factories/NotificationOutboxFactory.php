<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationOutbox>
 */
class NotificationOutboxFactory extends Factory
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
            'event_type' => 'test.notification',
            'channel' => 'database',
            'payload' => [
                'title' => fake()->sentence(3),
                'message' => fake()->sentence(),
                'data' => ['source' => 'factory'],
            ],
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 5,
            'available_at' => now(),
        ];
    }
}
