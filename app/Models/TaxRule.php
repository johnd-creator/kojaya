<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    /** @use HasFactory<\Database\Factories\TaxRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'year',
        'effective_from',
        'effective_until',
        'regulation_reference',
        'ptkp_amounts',
        'progressive_layers',
        'biaya_jabatan_rate',
        'biaya_jabatan_max',
        'no_npwp_surcharge_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'ptkp_amounts' => 'array',
            'progressive_layers' => 'array',
            'biaya_jabatan_rate' => 'float',
            'biaya_jabatan_max' => 'float',
            'no_npwp_surcharge_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     year: int,
     *     effective_from: string,
     *     effective_until: string|null,
     *     regulation_reference: string,
     *     ptkp_amounts: array<string, int>,
     *     progressive_layers: list<array{name: string, max: int|null, rate: float}>,
     *     biaya_jabatan_rate: float,
     *     biaya_jabatan_max: int,
     *     no_npwp_surcharge_rate: float,
     *     is_active: bool
     * }
     */
    public static function defaultPph21Ter2024(): array
    {
        return [
            'code' => 'PPH21_TER_2024',
            'name' => 'PPh21 TER 2024',
            'year' => 2024,
            'effective_from' => '2024-01-01',
            'effective_until' => null,
            'regulation_reference' => 'PP 58/2023 dan PMK 168/2023',
            'ptkp_amounts' => [
                'TK/0' => 54_000_000,
                'TK/1' => 58_500_000,
                'TK/2' => 63_000_000,
                'TK/3' => 67_500_000,
                'K/0' => 58_500_000,
                'K/1' => 63_000_000,
                'K/2' => 67_500_000,
                'K/3' => 72_000_000,
            ],
            'progressive_layers' => [
                ['name' => 'Layer 1 (0-60jt)', 'max' => 60_000_000, 'rate' => 0.05],
                ['name' => 'Layer 2 (60-250jt)', 'max' => 250_000_000, 'rate' => 0.15],
                ['name' => 'Layer 3 (250-500jt)', 'max' => 500_000_000, 'rate' => 0.25],
                ['name' => 'Layer 4 (500jt-5M)', 'max' => 5_000_000_000, 'rate' => 0.30],
                ['name' => 'Layer 5 (>5M)', 'max' => null, 'rate' => 0.35],
            ],
            'biaya_jabatan_rate' => 0.05,
            'biaya_jabatan_max' => 6_000_000,
            'no_npwp_surcharge_rate' => 0.20,
            'is_active' => true,
        ];
    }
}
