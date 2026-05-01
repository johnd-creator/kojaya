<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponentType extends Model
{
    protected $fillable = ['code', 'name', 'is_taxable', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function structureItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalaryStructureItem::class);
    }
}
