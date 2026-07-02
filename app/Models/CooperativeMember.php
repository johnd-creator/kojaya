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
        'validation_status',
        'validated_at',
        'validated_by',
        'validation_notes',
        'admin_validated_at',
        'admin_validated_by',
        'admin_validation_notes',
        'profile_completed_at',
        'onboarding_submitted_at',
        'sso_provider',
        'last_sso_login_at',
        'npwp',
        'no_telp',
        'jenis_anggota',
        'jenis_kelamin',
        'kategori',
        'autodebet',
        'no_rekening',
        'nama_bank',
        'nama_pemilik_rekening',
        'tanggal_lahir',
        'tempat_lahir',
        'pekerjaan',
        'perusahaan',
        'notes',
        'credit_limit',
        'outstanding_balance',
        'credit_term_days',
        'credit_tier',
    ];

    public const VALIDATION_PENDING = 'PENDING';

    public const VALIDATION_PENDING_REVIEW = 'PENDING_VALIDATION';

    public const VALIDATION_ACTIVE = 'ACTIVE';

    public const VALIDATION_INACTIVE = 'INACTIVE';

    public const VALIDATION_REJECTED = 'REJECTED';

    public const VALIDATION_REVISION = 'REVISION';

    public const VALIDATION_RESIGNED = 'RESIGNED';

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
            'tanggal_lahir' => 'date',
            'deleted_at' => 'datetime',
            'validated_at' => 'datetime',
            'admin_validated_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'onboarding_submitted_at' => 'datetime',
            'last_sso_login_at' => 'datetime',
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
        ];
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(PosMemberCreditPayment::class);
    }

    public function openingBalanceBatches(): HasMany
    {
        return $this->hasMany(CooperativeMemberOpeningBalanceBatch::class, 'cooperative_member_id');
    }

    public function activeOpeningBalanceBatch(): ?CooperativeMemberOpeningBalanceBatch
    {
        return $this->openingBalanceBatches()
            ->where('status', \App\Enums\Cooperative\OpeningBalanceBatchStatus::Posted->value)
            ->latest('posted_at')
            ->first();
    }

    public function availableCredit(): float
    {
        return max((float) $this->credit_limit - (float) $this->outstanding_balance, 0);
    }

    public function hasAvailableCredit(float $amount): bool
    {
        $limit = (float) $this->credit_limit;
        if ($limit <= 0) {
            return false;
        }

        return $this->availableCredit() >= $amount;
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

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function adminValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_validated_by');
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

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(MemberPaymentIntent::class);
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

    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'cooperative_member_id');
    }

    public function posReturns(): HasMany
    {
        return $this->hasMany(PosReturn::class, 'cooperative_member_id');
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

    public function resignationRequests(): HasMany
    {
        return $this->hasMany(MemberResignationRequest::class);
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
