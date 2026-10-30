<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Experts\ExpertDirectory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Annuaire des experts vérifiés avec leurs tarifs (SE-01).
 */
class ExpertController extends Controller
{
    public function index(Request $request, ExpertDirectory $directory): View
    {
        $filters = array_filter($request->only(['q', 'sector', 'region']), fn ($value) => filled($value));

        return view('experts.index', [
            'experts' => $directory->query($filters)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'sectors' => Sector::options('slug'),
        ]);
    }
}
