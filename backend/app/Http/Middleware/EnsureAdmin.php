<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Требуется авторизация.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Недостаточно прав. Требуется роль администратора.'], 403);
        }

        if ($user->isBlocked()) {
            return response()->json(['message' => 'Аккаунт заблокирован.'], 403);
        }

        return $next($request);
    }
}
