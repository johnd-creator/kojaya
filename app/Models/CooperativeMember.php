<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CooperativeMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'user_id',
        'no_anggota',
        'tanggal_aktif',
        'nama_anggota',
        'member_no',
        'name',
        'email',
        'phone',
        'identity_number',
        'address',
        'joined_at',
        'resigned_at',
        'status',
        'npwp',
        'no_telp',
        'jenis_anggota',
        'jenis_kelamin',
        'kategori',
        'autodebet',
        'no_rekening',
        'notes',
    ];

    protected $appends = [
        'nama_anggota_clean',
        'jenis_anggota_label',
        'status_badge',
        'no_anggota_display',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_aktif' => 'date',
            'joined_at' => 'date',
            'resigned_at' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CooperativeMemberDocument::class);
    }

    public function onboardingProgress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberOnboardingProgress::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CooperativeDuesInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CooperativePayment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CooperativeReceipt::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function posMemberPoints(): HasMany
    {
        return $this->hasMany(PosMemberPoint::class, 'cooperative_member_id');
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'cooperative_member_id');
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class, 'cooperative_member_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CooperativeLedgerEntry::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(CooperativeSupportTicket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeNonAktif($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    public function getNamaAnggotaCleanAttribute(): string
    {
        return rtrim(rtrim($this->nama_anggota ?: $this->name, '*'));
    }

    public function getJenisAnggotaLabelAttribute(): string
    {
        return match ($this->jenis_anggota) {
            'ALB' => 'Anggota Luar Biasa',
            default => 'Anggota Biasa',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'ACTIVE' => ['label' => 'AKTIF', 'variant' => 'success'],
            'INACTIVE', 'RESIGNED' => ['label' => 'NON-AKTIF', 'variant' => 'secondary'],
            default => ['label' => $this->status, 'variant' => 'warning'],
        };
    }

    public function getNoAnggotaDisplayAttribute(): string
    {
        return $this->no_anggota ?: $this->member_no;
    }
}
