<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Models\Traits\HasApprovalLog;
use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class PurchaseOrder extends Model
{
    use HasApprovalLog, HasFactory, HasOrganizationScope, HasUuids;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'purchase_request_id',
        'vendor_id',
        'warehouse_id',
        'po_no',
        'status',
        'total_amount',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseOrder $purchaseOrder): void {
            if (! $purchaseOrder->vendor_id) {
                return;
            }

            $vendor = $purchaseOrder->relationLoaded('vendor')
                ? $purchaseOrder->vendor
                : Vendor::query()->find($purchaseOrder->vendor_id);

            if (! $vendor || ! in_array($vendor->status, [VendorStatus::Suspended, VendorStatus::Blacklisted], true)) {
                return;
            }

            throw ValidationException::withMessages([
                'vendor_id' => 'Vendor suspended atau blacklisted tidak dapat dipakai untuk purchase order.',
            ]);
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
