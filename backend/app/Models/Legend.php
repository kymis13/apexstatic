<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Legend extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'real_name',
        'role',
        'difficulty',
        'tagline',
        'lore',
        'passive_name',
        'passive_desc',
        'tactical_name',
        'tactical_desc',
        'ultimate_name',
        'ultimate_desc',
        'forte',
        'tip',
        'image',
        'accent_color',
        'pick_rate',
        'release_season',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'pick_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function stats(): HasMany
    {
        return $this->hasMany(LegendStat::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
