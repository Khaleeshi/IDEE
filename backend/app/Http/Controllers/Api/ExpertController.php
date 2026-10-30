<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Services\Experts\ExpertDirectory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Annuaire des experts vérifiés avec leurs tarifs (SE-01).
 */
class ExpertController extends Controller
{
    /**
     * Filtres : `q`, `sector`, `region`.
     */
    public function index(Request $request, ExpertDirectory $directory): AnonymousResourceCollection
    {
        return MemberResource::collection(
            $directory->query(array_filter($request->only(['q', 'sector', 'region']), fn ($value) => filled($value)))->paginate(20)
        );
    }
}
