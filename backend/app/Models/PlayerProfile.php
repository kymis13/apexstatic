<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'steam_id',
        'level',
        'rank_tier',
        'rank_points',
        'hours_played',
        'total_kills',
        'wins',
        'total_damage',
        'matches_played',
        'kd',
        'winrate',
        'headshot_rate',
        'battle_pass',
        'stats_status',
        'stats_error',
        'main_legend_id',
        'country',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'rank_points' => 'integer',
            'hours_played' => 'integer',
            'total_kills' => 'integer',
            'wins' => 'integer',
            'total_damage' => 'integer',
            'matches_played' => 'integer',
            'kd' => 'float',
            'winrate' => 'float',
            'headshot_rate' => 'float',
            'battle_pass' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mainLegend(): BelongsTo
    {
        return $this->belongsTo(Legend::class, 'main_legend_id');
    }

    public function legendStats(): HasMany
    {
        return $this->hasMany(LegendStat::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }
}
