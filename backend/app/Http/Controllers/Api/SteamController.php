<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SteamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SteamController extends Controller
{
    private const OPENID_ENDPOINT = 'https://steamcommunity.com/openid/login';

    public function redirect(Request $request): JsonResponse
    {
        $state = Str::random(48);

        Cache::put('steam:state:'.$state, true, now()->addMinutes(10));

        $returnUrl = config('services.steam.return_url').'?state='.$state;

        $query = http_build_query([
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnUrl,
            'openid.realm' => config('services.steam.realm'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ]);

        return response()->json([
            'url' => self::OPENID_ENDPOINT.'?'.$query,
            'state' => $state,
        ]);
    }

    public function callback(Request $request, SteamService $steam)
    {
        $frontend = rtrim(config('services.frontend_url'), '/');
        $params = $request->query();

        if (($params['openid.mode'] ?? null) !== 'id_res') {
            return redirect($frontend.'/login?steam=cancelled');
        }

        $state = $params['state'] ?? null;

        if (! $state || ! Cache::pull('steam:state:'.$state)) {
            return redirect($frontend.'/login?steam=invalid_state');
        }

        if (! $this->verifyAssertion($params)) {
            return redirect($frontend.'/login?steam=invalid_signature');
        }

        $claimedId = $params['openid.claimed_id'] ?? '';
        preg_match('#^https?://steamcommunity\.com/openid/id/(\d{17})$#', $claimedId, $matches);

        if (! isset($matches[1])) {
            return redirect($frontend.'/login?steam=invalid_id');
        }

        $steamId = $matches[1];

        $user = User::where('steam_id', $steamId)->first();

        if ($user?->isBlocked()) {
            return redirect($frontend.'/login?steam=blocked');
        }

        $steamProfile = $steam->profileXml($steamId) ?? [];

        if (! $user) {
            $user = DB::transaction(function () use ($steamId, $steamProfile) {
                $nickname = $this->uniqueNickname($steamProfile['nickname'] ?? 'Steam Player', $steamId);
                $isAdmin = in_array($steamId, config('services.steam.admin_ids', []), true);

                $user = User::create([
                    'nickname' => $nickname,
                    'email' => 'steam_'.$steamId.'@apexstats.local',
                    'password' => Hash::make(Str::random(64)),
                    'role' => $isAdmin ? 'admin' : 'player',
                    'steam_id' => $steamId,
                    'steam_name' => $steamProfile['nickname'] ?? $nickname,
                    'avatar' => $steamProfile['avatar'] ?? null,
                ]);

                $user->playerProfile()->create([
                    'steam_id' => $steamId,
                    'level' => 1,
                    'rank_tier' => 'Бронза IV',
                    'rank_points' => 0,
                    'hours_played' => 0,
                    'total_kills' => 0,
                    'wins' => 0,
                    'total_damage' => 0,
                    'matches_played' => 0,
                    'kd' => 0,
                    'winrate' => 0,
                    'headshot_rate' => 0,
                    'battle_pass' => 0,
                    'stats_status' => 'pending',
                    'country' => 'RU',
                ]);

                AuditLog::record($user, 'Регистрация через Steam', 'user', $user->id, ['steam_id' => $steamId]);

                return $user;
            });
        } else {
            $user->forceFill([
                'steam_name' => $steamProfile['nickname'] ?? $user->steam_name,
                'avatar' => $steamProfile['avatar'] ?? $user->avatar,
                'last_login_at' => now(),
            ])->save();

            $user->playerProfile()->firstOrCreate([], [
                'steam_id' => $steamId,
                'level' => 1,
                'rank_tier' => 'Бронза IV',
                'rank_points' => 0,
                'hours_played' => 0,
                'total_kills' => 0,
                'wins' => 0,
                'total_damage' => 0,
                'matches_played' => 0,
                'kd' => 0,
                'winrate' => 0,
                'headshot_rate' => 0,
                'battle_pass' => 0,
                'stats_status' => 'pending',
                'country' => 'RU',
            ]);
        }

        $token = $user->createToken('steam-openid')->plainTextToken;
        $code = Str::random(48);

        Cache::put('steam:login:'.$code, [
            'token' => $token,
            'user_id' => $user->id,
        ], now()->addMinutes(3));

        AuditLog::record($user, 'Вход через Steam', 'user', $user->id);

        return redirect($frontend.'/steam?code='.$code);
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:48'],
        ]);

        $payload = Cache::pull('steam:login:'.$validated['code']);

        if (! $payload) {
            return response()->json([
                'message' => 'Код входа истёк. Войдите через Steam ещё раз.',
            ], 422);
        }

        $user = User::find($payload['user_id']);

        if (! $user || $user->isBlocked()) {
            return response()->json([
                'message' => 'Аккаунт недоступен.',
            ], 403);
        }

        return response()->json([
            'token' => $payload['token'],
            'tokenType' => 'Bearer',
            'user' => new UserResource($user->load('playerProfile.mainLegend')),
        ]);
    }

    public function unlink(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill(['steam_id' => null])->save();
        $user->playerProfile()->update(['steam_id' => null]);

        AuditLog::record($user, 'Отвязан Steam-аккаунт', 'user', $user->id);

        return response()->json(['message' => 'Steam-аккаунт отвязан.']);
    }

    private function verifyAssertion(array $params): bool
    {
        $params['openid.mode'] = 'check_authentication';

        try {
            $response = Http::asForm()->timeout(15)->post(self::OPENID_ENDPOINT, $params);

            return $response->successful() && str_contains($response->body(), 'is_valid:true');
        } catch (\Throwable) {
            return false;
        }
    }

    private function uniqueNickname(string $base, string $steamId): string
    {
        $base = mb_substr(trim($base) ?: 'Steam Player', 0, 24);
        $nickname = $base;

        if (! User::where('nickname', $nickname)->exists()) {
            return $nickname;
        }

        $nickname = $base.' #'.substr($steamId, -4);
        $counter = 1;

        while (User::where('nickname', $nickname)->exists()) {
            $nickname = $base.' #'.substr($steamId, -4).'-'.$counter++;
        }

        return $nickname;
    }
}
