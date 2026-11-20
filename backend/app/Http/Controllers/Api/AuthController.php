<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'nickname' => $validated['nickname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'player',
                'avatar' => '/assets/avatars/recruit.svg',
            ]);

            $user->playerProfile()->create([
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
                'country' => 'RU',
            ]);

            AuditLog::record($user, 'Регистрация пользователя', 'user', $user->id);

            return $user;
        });

        $token = $user->createToken($validated['device_name'] ?? 'spa')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('playerProfile')),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        if ($user->isBlocked()) {
            return response()->json([
                'message' => 'Аккаунт заблокирован. Обратитесь в поддержку.',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($validated['device_name'] ?? 'spa')->plainTextToken;

        AuditLog::record($user, 'Вход в аккаунт', 'user', $user->id);

        return response()->json([
            'user' => new UserResource($user->load('playerProfile.mainLegend')),
            'token' => $token,
            'tokenType' => 'Bearer',
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('playerProfile.mainLegend'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Вы вышли из аккаунта.']);
    }
}
