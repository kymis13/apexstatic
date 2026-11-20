<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegendStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'legend' => new LegendResource($this->whenLoaded('legend')),
            'matches' => $this->matches,
            'kills' => $this->kills,
            'wins' => $this->wins,
            'damage' => $this->damage,
            'playtime' => $this->playtime_hours,
            'bestSeason' => $this->best_season,
            'winrate' => $this->winrate,
        ];
    }
}
