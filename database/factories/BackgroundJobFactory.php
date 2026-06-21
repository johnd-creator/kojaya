<?php

namespace Database\Factories;

use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackgroundJob>
 */
class BackgroundJobFactory extends Factory
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
            'type' => 'pos.report.pdf',
            'status' => BackgroundJobStatus::Pending,
            'progress' => 0,
            'metadata' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => BackgroundJobStatus::Processing,
            'progress' => 50,
            'started_at' => now(),
        ]);
    }

    public function completed(string $filePath = 'reports/example.pdf'): static
    {
        return $this->state(fn () => [
            'status' => BackgroundJobStatus::Completed,
            'progress' => 100,
            'file_path' => $filePath,
            'original_name' => basename($filePath),
            'mime_type' => 'application/pdf',
            'file_size' => 12345,
            'finished_at' => now(),
        ]);
    }

    public function failed(string $message = 'Job error'): static
    {
        return $this->state(fn () => [
            'status' => BackgroundJobStatus::Failed,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }
}
