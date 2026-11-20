<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tier' => ['sometimes', 'string', 'max:30'],
            'country' => ['sometimes', 'string', 'size:2'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $key = 'leaderboard:'.md5(json_encode($filters));

        $rows = Cache::remember($key, 120, function () use ($filters) {
            return PlayerProfile::query()
                ->with(['user:id,nickname,avatar,steam_name', 'mainLegend:id,slug,name,image,role'])
                ->when(isset($filters['tier']), fn ($q) => $q->where('rank_tier', 'like', $filters['tier'].'%'))
                ->when(isset($filters['country']), fn ($q) => $q->where('country', $filters['country']))
                ->orderByDesc('rank_points')
                ->limit($filters['limit'] ?? 50)
                ->get()
                ->values()
                ->map(function (PlayerProfile $profile, int $index) {
                    return [
                        'rank' => $index + 1,
                        'nickname' => $profile->user?->nickname,
                        'tag' => $profile->user?->steam_name,
                        'avatar' => $profile->user?->avatar,
                        'legend' => $profile->mainLegend?->slug,
                        'legendName' => $profile->mainLegend?->name,
                        'legendImage' => $profile->mainLegend?->image,
                        'tier' => $profile->rank_tier,
                        'rp' => $profile->rank_points,
                        'hours' => $profile->hours_played,
                        'kills' => $profile->total_kills,
                        'winrate' => $profile->winrate,
                        'kd' => $profile->kd,
                        'country' => $profile->country,
                    ];
                });
        });

        return response()->json(['data' => $rows]);
    }
}
