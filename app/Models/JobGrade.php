<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobGrade extends Model
{
    protected $fillable = ['code', 'name', 'level'];

    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function salaryStructures(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }
}
