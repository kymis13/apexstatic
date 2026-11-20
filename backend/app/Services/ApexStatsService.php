<?php

namespace App\Services;

use App\Models\Legend;
use App\Models\LegendStat;
use App\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ApexStatsService
{
    public function __construct(
        private readonly SteamService $steam,
        private readonly ApexProfileScraper $apex,
    ) {
    }

    public function syncProfile(PlayerProfile $profile): PlayerProfile
    {
        $steamId = $profile->steam_id ?: $profile->user?->steam_id;

        if (! $steamId) {
            throw new RuntimeException('Steam ID не привязан к профилю.');
        }

        $hours = $this->steam->apexHours($steamId);

        try {
            $data = $this->apex->fetch($steamId);
        } catch (Throwable $exception) {
            $profile->forceFill([
                'stats_status' => 'error',
                'stats_error' => mb_substr($exception->getMessage(), 0, 250),
                'last_synced_at' => now(),
            ])->save();

            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }

        if (! $data) {
            $profile->forceFill([
                'stats_status' => 'not_found',
                'stats_error' => 'Профиль Apex Legends не найден. Проверьте, что вы играли в этом сезоне и профиль Steam открыт.',
                'last_synced_at' => now(),
            ])->save();

            return $profile->fresh(['mainLegend', 'user']);
        }

        DB::transaction(function () use ($profile, $data, $hours) {
            $selected = $data['selected_legend'] ? $this->findLegend($data['selected_legend']) : null;

            $profile->forceFill([
                'level' => max(1, (int) $data['level']),
                'rank_tier' => $data['rank_tier'] ?: $profile->rank_tier,
                'rank_points' => (int) $data['rank_points'],
                'total_kills' => (int) ($data['global']['kills'] ?? 0),
                'wins' => (int) ($data['global']['wins'] ?? 0),
                'total_damage' => (int) ($data['global']['damage'] ?? 0),
                'main_legend_id' => $selected?->id ?? $profile->main_legend_id,
                'stats_status' => 'ok',
                'stats_error' => null,
                'last_synced_at' => now(),
            ]);

            if ($hours !== null) {
                $profile->hours_played = $hours;
            }

            $profile->save();

            foreach ($data['legends'] as $row) {
                $legend = $this->findLegend($row['name']);

                if (! $legend) {
                    continue;
                }

                LegendStat::updateOrCreate(
                    [
                        'player_profile_id' => $profile->id,
                        'legend_id' => $legend->id,
                    ],
                    [
                        'kills' => (int) $row['kills'],
                        'wins' => (int) $row['wins'],
                        'damage' => (int) $row['damage'],
                    ],
                );
            }
        });

        return $profile->fresh(['mainLegend', 'user']);
    }

    private function findLegend(string $name): ?Legend
    {
        return Legend::where('name', $name)->first()
            ?? Legend::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }
}
