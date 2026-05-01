<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'tax_id',
        'contact_person',
        'phone',
        'email',
        'client_type',
        'organization_id',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function scopePln($query)
    {
        return $query->where('client_type', 'PLN');
    }

    public function scopePrivate($query)
    {
        return $query->where('client_type', 'PRIVATE');
    }
}
