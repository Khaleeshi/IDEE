<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfessionCategory;
use Illuminate\Http\JsonResponse;

class ProfessionController extends Controller
{
    /**
     * Référentiel des métiers actifs, par catégorie (PM-01).
     */
    public function index(): JsonResponse
    {
        $categories = ProfessionCategory::query()
            ->orderBy('position')
            ->with(['professions' => fn ($query) => $query->where('active', true)])
            ->get()
            ->filter(fn (ProfessionCategory $category) => $category->professions->isNotEmpty())
            ->map(fn (ProfessionCategory $category) => [
                'slug' => $category->slug,
                'name' => $category->name,
                'professions' => $category->professions->map->only(['id', 'slug', 'name', 'description', 'regulated'])->values(),
            ])
            ->values();

        return response()->json(['data' => $categories]);
    }
}
