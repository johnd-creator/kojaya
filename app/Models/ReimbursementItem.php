<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reimbursement_id',
        'category',
        'description',
        'amount',
        'receipt_file_path',
        'receipt_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'receipt_date' => 'date',
    ];

    public function reimbursement()
    {
        return $this->belongsTo(Reimbursement::class);
    }
}
