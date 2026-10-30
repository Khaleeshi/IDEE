<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use App\Services\Alerts\SavedSearchService;
use App\Services\Projects\ProjectSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Alertes enregistrées sur de nouveaux projets (RR-04).
 */
class AlertController extends Controller
{
    public function __construct(private readonly SavedSearchService $searches) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->savedSearches->map($this->present(...))]);
    }

    /**
     * Enregistre les filtres de la recherche de projets (`q`, `sector`, `region`, `stage`, `need_type`, `amount_min`, `amount_max`).
     */
    public function store(Request $request): JsonResponse
    {
        $search = $this->searches->save($request->user(), $request->only(ProjectSearch::FILTERS));

        return response()->json(['data' => $this->present($search)], $search->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function destroy(Request $request, SavedSearch $savedSearch): Response
    {
        abort_unless($savedSearch->user_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        $savedSearch->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SavedSearch $search): array
    {
        return [
            'id' => $search->id,
            'label' => $search->label,
            'filters' => $search->filters,
            'checked_at' => $search->checked_at?->toIso8601String(),
        ];
    }
}
