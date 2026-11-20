<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'level' => $this->level,
            'rank' => $this->rank_tier,
            'rankPoints' => $this->rank_points,
            'hours' => $this->hours_played,
            'kills' => $this->total_kills,
            'wins' => $this->wins,
            'damage' => $this->total_damage,
            'matches' => $this->matches_played,
            'kd' => $this->kd,
            'winrate' => $this->winrate,
            'headshots' => $this->headshot_rate,
            'battlePass' => $this->battle_pass,
            'statsStatus' => $this->stats_status,
            'statsError' => $this->stats_error,
            'country' => $this->country,
            'mainLegend' => new LegendResource($this->whenLoaded('mainLegend')),
            'lastSync' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
