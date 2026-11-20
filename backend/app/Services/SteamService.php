<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class SteamService
{
    private const APEX_APP_ID = 1172470;

    private const USER_AGENT = 'APEX STATS / 1.0 (+https://vps.ktk-45.ru)';

    public function hasApiKey(): bool
    {
        return (bool) config('services.steam.key');
    }

    public function ownedGames(string $steamId): array
    {
        $key = config('services.steam.key');

        if (! $key) {
            return [];
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(12)
                ->retry(2, 300)
                ->get('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
                    'key' => $key,
                    'steamid' => $steamId,
                    'include_appinfo' => 0,
                    'include_played_free_games' => 1,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return $response->json('response.games', []);
        } catch (Throwable) {
            return [];
        }
    }

    public function apexHours(string $steamId): ?int
    {
        $games = $this->ownedGames($steamId);

        foreach ($games as $game) {
            if ((int) ($game['appid'] ?? 0) === self::APEX_APP_ID) {
                return (int) round(((int) ($game['playtime_forever'] ?? 0)) / 60);
            }
        }

        return null;
    }

    /**
     * Публичный XML-профиль Steam. Не требует API-ключа и даёт ник, аватар
     * и SteamID64 — этого достаточно для входа через OpenID.
     */
    public function profileXml(string $steamId): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(12)
                ->get("https://steamcommunity.com/profiles/{$steamId}/", ['xml' => 1]);

            if (! $response->successful()) {
                return null;
            }

            $xml = @simplexml_load_string($response->body());

            if (! $xml || (string) $xml->steamID64 !== $steamId) {
                return null;
            }

            return [
                'steam_id' => (string) $xml->steamID64,
                'nickname' => trim((string) $xml->steamID) ?: 'Steam Player',
                'avatar' => (string) $xml->avatarFull,
                'privacy' => (string) $xml->privacyState,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    public function playerSummary(string $steamId): ?array
    {
        $key = config('services.steam.key');

        if (! $key) {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(12)
                ->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/', [
                    'key' => $key,
                    'steamids' => $steamId,
                ]);

            return $response->successful() ? $response->json('response.players.0') : null;
        } catch (Throwable) {
            return null;
        }
    }
}
