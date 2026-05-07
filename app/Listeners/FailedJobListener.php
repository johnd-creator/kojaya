<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class FailedJobListener
{
    public function handle(JobFailed $event): void
    {
        $payload = [
            'job_class' => $event->job->resolveName(),
            'exception_class' => get_class($event->exception),
            'exception_message' => $event->exception->getMessage(),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'failed_at' => now()->toIso8601String(),
        ];

        Log::error('Job failed', $payload);

        $admins = \App\Models\User::role(['System Admin', 'Admin Pusat'])->get();

        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'job_failed',
                'data' => [
                    'title' => 'Job Failure Alert',
                    'message' => "Job {$payload['job_class']} failed: {$payload['exception_message']}",
                    'job_class' => $payload['job_class'],
                    'exception_class' => $payload['exception_class'],
                    'failed_at' => $payload['failed_at'],
                ],
            ]);
        }
    }
}
