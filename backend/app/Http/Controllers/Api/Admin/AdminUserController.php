<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('playerProfile');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('nickname', 'like', $search)->orWhere('email', 'like', $search));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('is_blocked', $request->string('status') === 'blocked');
        }

        $users = $query->latest()->paginate(25);

        return UserResource::collection($users)->response();
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        $actor = $request->user();

        if ($actor->id === $user->id && ($request->has('role') || $request->has('status'))) {
            return response()->json(['message' => 'Нельзя менять собственную роль или блокировать себя.'], 422);
        }

        if ($request->has('role') && $user->isAdmin() && $request->string('role') === 'player') {
            $admins = User::where('role', 'admin')->count();

            if ($admins <= 1) {
                return response()->json(['message' => 'Нельзя снять роль с последнего администратора.'], 422);
            }
        }

        $data = [];

        if ($request->has('role')) {
            $data['role'] = $request->string('role')->toString();
        }

        if ($request->has('status')) {
            $data['is_blocked'] = $request->string('status') === 'blocked';
        }

        if ($request->filled('nickname')) {
            $data['nickname'] = $request->string('nickname')->toString();
        }

        $user->update($data);

        AuditLog::record($actor, 'Изменён пользователь', 'user', $user->id, $data);

        return new UserResource($user->fresh('playerProfile'));
    }
}
