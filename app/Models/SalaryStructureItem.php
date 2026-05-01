<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructureItem extends Model
{
    protected $fillable = ['salary_structure_id', 'salary_component_type_id', 'amount'];

    public function structure(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function componentType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SalaryComponentType::class, 'salary_component_type_id');
    }
}
