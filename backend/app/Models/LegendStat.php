<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegendStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'legend_id',
        'matches',
        'kills',
        'wins',
        'damage',
        'playtime_hours',
        'best_season',
        'winrate',
    ];

    protected function casts(): array
    {
        return [
            'matches' => 'integer',
            'kills' => 'integer',
            'wins' => 'integer',
            'damage' => 'integer',
            'playtime_hours' => 'integer',
            'winrate' => 'float',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }

    public function legend(): BelongsTo
    {
        return $this->belongsTo(Legend::class);
    }
}
