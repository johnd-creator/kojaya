<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGrade extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'level'];

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
