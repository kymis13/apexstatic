<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLegendRequest;
use App\Http\Requests\Admin\UpdateLegendRequest;
use App\Http\Resources\LegendResource;
use App\Models\AuditLog;
use App\Models\Legend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLegendController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Legend::query();

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('real_name', 'like', $search));
        }

        return response()->json([
            'data' => LegendResource::collection($query->orderByDesc('pick_rate')->get()),
        ]);
    }

    public function store(StoreLegendRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['image'] = $data['image'] ?? '/assets/legends/'.$data['slug'].'.webp';
        $data['accent_color'] = $data['accent_color'] ?? '#ff4d3d';
        $data['is_active'] = $data['is_active'] ?? true;

        $legend = Legend::create($data);

        AuditLog::record($request->user(), 'Создана легенда', 'legend', $legend->id, ['name' => $legend->name]);

        return (new LegendResource($legend))->response()->setStatusCode(201);
    }

    public function update(UpdateLegendRequest $request, Legend $legend): LegendResource
    {
        $legend->update($request->validated());

        AuditLog::record($request->user(), 'Обновлена легенда', 'legend', $legend->id, $request->validated());

        return new LegendResource($legend);
    }

    public function destroy(Request $request, Legend $legend): JsonResponse
    {
        if ($legend->stats()->exists() || $legend->matches()->exists()) {
            $legend->update(['is_active' => false]);

            AuditLog::record($request->user(), 'Легенда снята с публикации', 'legend', $legend->id);

            return response()->json([
                'message' => 'Легенда связана со статистикой игроков, поэтому снята с публикации вместо удаления.',
            ]);
        }

        $legend->delete();

        AuditLog::record($request->user(), 'Удалена легенда', 'legend', $legend->id, ['name' => $legend->name]);

        return response()->json(['message' => 'Легенда удалена.']);
    }
}
