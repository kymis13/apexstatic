<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegendResource;
use App\Models\Legend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LegendController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Legend::query()->active();

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('real_name', 'like', $search));
        }

        $legends = $query->orderByDesc('pick_rate')->get();

        return LegendResource::collection($legends);
    }

    public function show(Legend $legend): LegendResource
    {
        abort_unless($legend->is_active, 404);

        return new LegendResource($legend);
    }
}
