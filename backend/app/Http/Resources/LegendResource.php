<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'realName' => $this->real_name,
            'role' => $this->role,
            'difficulty' => $this->difficulty,
            'tagline' => $this->tagline,
            'lore' => $this->lore,
            'passive' => ['name' => $this->passive_name, 'desc' => $this->passive_desc],
            'tactical' => ['name' => $this->tactical_name, 'desc' => $this->tactical_desc],
            'ultimate' => ['name' => $this->ultimate_name, 'desc' => $this->ultimate_desc],
            'forte' => $this->forte,
            'tip' => $this->tip,
            'image' => $this->image,
            'accent' => $this->accent_color,
            'pickRate' => $this->pick_rate,
            'release' => $this->release_season,
            'isActive' => $this->is_active,
        ];
    }
}
