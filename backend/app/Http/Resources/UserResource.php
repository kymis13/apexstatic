<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->is_blocked ? 'blocked' : 'active',
            'steamId' => $this->steam_id,
            'steamName' => $this->steam_name,
            'avatar' => $this->avatar,
            'twoFactor' => $this->two_factor_secret !== null,
            'createdAt' => $this->created_at?->toDateString(),
            'lastSeen' => $this->last_login_at?->diffForHumans(),
            'profile' => new PlayerProfileResource($this->whenLoaded('playerProfile')),
        ];
    }
}
