<?php

namespace App\Models;

use App\Enums\Co\Pos\BackgroundJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BackgroundJob extends Model
{
    /** @use HasFactory<\Database\Factories\BackgroundJobFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'status',
        'progress',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'error_message',
        'metadata',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BackgroundJobStatus::class,
            'progress' => 'integer',
            'file_size' => 'integer',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            if (empty($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }
            if (empty($job->status)) {
                $job->status = BackgroundJobStatus::Pending;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => BackgroundJobStatus::Processing,
            'started_at' => $this->started_at ?? now(),
        ])->save();
    }

    public function markCompleted(string $filePath, ?string $originalName = null, ?string $mimeType = null, ?int $fileSize = null): void
    {
        $this->forceFill([
            'status' => BackgroundJobStatus::Completed,
            'progress' => 100,
            'file_path' => $filePath,
            'original_name' => $originalName ?? basename($filePath),
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'finished_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => BackgroundJobStatus::Failed,
            'error_message' => mb_substr($message, 0, 1000),
            'finished_at' => now(),
        ])->save();
    }

    public function updateProgress(int $percent): void
    {
        $percent = max(0, min(100, $percent));
        $this->forceFill(['progress' => $percent])->save();
    }
}
