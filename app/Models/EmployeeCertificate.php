<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCertificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'certificate_type',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'document_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'certificate_type' => CertificateType::class,
        'status' => CertificateStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeValid($query)
    {
        return $query->where('status', CertificateStatus::VALID);
    }

    public function scopeExpiring($query, int $days = 60)
    {
        return $query->where('status', CertificateStatus::VALID)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', CertificateStatus::EXPIRED)
            ->orWhere('expiry_date', '<', now());
    }

    public function isExpiring(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays(60)) && $this->expiry_date->gt(now());
    }

    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    protected static function booted()
    {
        static::saving(function ($certificate) {
            if ($certificate->expiry_date && $certificate->expiry_date->isPast()) {
                $certificate->status = CertificateStatus::EXPIRED;
            }
        });
    }
}
