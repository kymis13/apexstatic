<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Legend;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $since = now()->subDays(7);

        return response()->json([
            'stats' => [
                'users' => User::count(),
                'activeUsers' => User::where('is_blocked', false)->count(),
                'blockedUsers' => User::where('is_blocked', true)->count(),
                'pendingRequests' => VerificationRequest::where('status', 'pending')->count(),
                'legends' => Legend::count(),
                'activeLegends' => Legend::where('is_active', true)->count(),
                'newUsersWeek' => User::where('created_at', '>=', $since)->count(),
                'profiles' => PlayerProfile::count(),
            ],
            'recentRequests' => VerificationRequest::with('user:id,nickname,email')
                ->latest()
                ->limit(5)
                ->get(),
            'recentAudit' => AuditLog::with('user:id,nickname')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function auditLogs(): JsonResponse
    {
        return response()->json(
            AuditLog::with('user:id,nickname')
                ->latest()
                ->paginate(50)
        );
    }
}
