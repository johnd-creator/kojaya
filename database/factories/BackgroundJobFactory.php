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

    public function withoutReportScope(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['__omit_report_scope__'] = true;

            return ['metadata' => $metadata];
        });
    }

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\BackgroundJob $job) {
            $metadata = $job->metadata ?? [];
            if (! is_array($metadata)) {
                return;
            }

            if (! empty($metadata['__omit_report_scope__'])) {
                unset($metadata['__omit_report_scope__'], $metadata['report_scope']);
                $job->metadata = $metadata;

                return;
            }

            if (array_key_exists('report_scope', $metadata)) {
                return;
            }

            if ($job->type === 'pos.report.pdf') {
                $user = $job->user ?? ($job->user_id ? User::find($job->user_id) : null);
                if ($user) {
                    $metadata['report_scope'] = [
                        'version' => 1,
                        'mode' => $user->organization_id ? 'organization' : 'global',
                        'organization_id' => $user->organization_id ? (string) $user->organization_id : null,
                    ];
                    $job->metadata = $metadata;
                }
            }
        });
    }
}
