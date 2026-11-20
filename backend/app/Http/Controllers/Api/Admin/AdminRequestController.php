<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewRequestRequest;
use App\Models\AuditLog;
use App\Models\VerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VerificationRequest::query()->with(['user:id,nickname,email', 'reviewer:id,nickname']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('type', 'like', $search)->orWhere('message', 'like', $search));
        }

        return response()->json($query->latest()->paginate(30));
    }

    public function update(ReviewRequestRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $verificationRequest->update([
            'status' => $request->validated('status'),
            'admin_comment' => $request->validated('admin_comment'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record(
            $request->user(),
            $request->validated('status') === 'approved' ? 'Одобрена заявка' : 'Отклонена заявка',
            'verification_request',
            $verificationRequest->id,
        );

        return response()->json([
            'message' => $request->validated('status') === 'approved' ? 'Заявка одобрена.' : 'Заявка отклонена.',
            'request' => $verificationRequest->fresh(['user:id,nickname', 'reviewer:id,nickname']),
        ]);
    }
}
