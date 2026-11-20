<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVerificationRequest;
use App\Http\Resources\LegendStatResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\PlayerProfileResource;
use App\Models\AuditLog;
use App\Models\VerificationRequest;
use App\Services\ApexStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StatsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->playerProfile()
            ->with(['mainLegend', 'legendStats.legend'])
            ->firstOrFail();

        $recent = $profile->matches()
            ->with('legend:id,slug,name,image')
            ->latest('played_at')
            ->limit(8)
            ->get();

        $weekly = $profile->matches()
            ->where('played_at', '>=', now()->subDays(63))
            ->get()
            ->groupBy(fn ($match) => $match->played_at->format('o-W'))
            ->map(function ($matches, $week) {
                return [
                    'week' => $matches->first()->played_at->startOfWeek()->format('d.m'),
                    'kills' => $matches->sum('kills'),
                    'damage' => $matches->sum('damage'),
                    'wins' => $matches->where('placement', 1)->count(),
                    'matches' => $matches->count(),
                    'kd' => round($matches->sum('kills') / max($matches->count(), 1), 2),
                ];
            })
            ->sortKeys()
            ->values();

        return response()->json([
            'profile' => new PlayerProfileResource($profile),
            'weekly' => $weekly,
            'recentMatches' => MatchResource::collection($recent),
            'legendStats' => LegendStatResource::collection($profile->legendStats->sortByDesc('kills')->values()),
        ]);
    }

    public function legends(Request $request)
    {
        $stats = $request->user()->playerProfile()
            ->legendStats()
            ->with('legend')
            ->orderByDesc('kills')
            ->get();

        return LegendStatResource::collection($stats);
    }

    public function matches(Request $request)
    {
        $request->validate(['limit' => ['sometimes', 'integer', 'between:1,50']]);

        $matches = $request->user()->playerProfile()
            ->matches()
            ->with('legend:id,slug,name,image')
            ->latest('played_at')
            ->limit($request->integer('limit', 20))
            ->get();

        return MatchResource::collection($matches);
    }

    public function sync(Request $request, ApexStatsService $apex): JsonResponse
    {
        $user = $request->user();

        if (! $user->steam_id) {
            return response()->json([
                'message' => 'Сначала привяжите Steam-аккаунт.',
            ], 422);
        }

        try {
            $updated = $apex->syncProfile($user->playerProfile);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Не удалось загрузить статистику Apex Legends. Попробуйте позже.',
                'errors' => ['sync' => [$exception->getMessage()]],
            ], 502);
        }

        AuditLog::record($user, 'Синхронизация Steam', 'player_profile', $user->playerProfile->id);

        $message = match ($updated->stats_status) {
            'not_found' => 'Профиль Apex Legends не найден. Проверьте, что вы играли в этом сезоне и профиль Steam открыт.',
            'error' => 'Не удалось загрузить статистику Apex Legends.',
            default => 'Статистика синхронизирована.',
        };

        return response()->json([
            'message' => $message,
            'statsStatus' => $updated->stats_status,
            'profile' => new PlayerProfileResource($updated),
        ]);
    }

    public function storeVerificationRequest(StoreVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        $exists = VerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'У вас уже есть заявка в обработке. Дождитесь ответа администратора.',
            ], 429);
        }

        $verification = VerificationRequest::create([
            'user_id' => $user->id,
            'type' => $request->validated('type'),
            'message' => $request->validated('message'),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        AuditLog::record($user, 'Создана заявка', 'verification_request', $verification->id);

        return response()->json([
            'message' => 'Заявка отправлена. Ответ придёт на email.',
            'request' => $verification,
        ], 201);
    }
}
