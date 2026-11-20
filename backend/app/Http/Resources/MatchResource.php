<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legend' => $this->legend?->slug,
            'legendName' => $this->legend?->name,
            'legendImage' => $this->legend?->image,
            'map' => $this->map,
            'mode' => $this->mode,
            'placement' => $this->placement,
            'kills' => $this->kills,
            'damage' => $this->damage,
            'rp' => $this->rp,
            'result' => $this->placement === 1 ? 'win' : ($this->placement <= 5 ? 'top' : 'loss'),
            'playedAt' => $this->played_at?->toIso8601String(),
        ];
    }
}
