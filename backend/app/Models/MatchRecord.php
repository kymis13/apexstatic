<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'legend_id',
        'map',
        'mode',
        'placement',
        'kills',
        'damage',
        'rp',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'placement' => 'integer',
            'kills' => 'integer',
            'damage' => 'integer',
            'rp' => 'integer',
            'played_at' => 'datetime',
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
