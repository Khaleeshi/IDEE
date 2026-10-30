<?php

namespace App\Http\Controllers\Web;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Feed\FeedService;
use App\Services\Projects\ProjectSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    private const FILTERS = ['q', 'sector', 'region', 'stage', 'need_type', 'amount_min', 'amount_max'];

    /**
     * Accueil : le fil d'activité (FC-01), ou la liste des projets dès qu'une recherche est lancée (RR-01).
     */
    public function index(Request $request, ProjectSearch $search, FeedService $feed): View
    {
        $filters = array_filter($request->only(self::FILTERS), fn ($value) => $value !== null && $value !== '');
        $tab = $filters || $request->query('vue') === 'projets' ? 'projets' : 'activite';

        return view('feed.index', [
            'tab' => $tab,
            'projects' => $tab === 'projets' ? $search->query($filters)->paginate(15)->withQueryString() : null,
            'activities' => $tab === 'activite' ? $feed->query()->paginate(20)->withQueryString() : null,
            'filters' => $filters,
            'sectors' => Sector::options('slug'),
            'needTypes' => collect(NeedType::cases())->mapWithKeys(fn (NeedType $type) => [$type->value => $type->label()]),
            'stages' => collect(ProjectStage::cases())->mapWithKeys(fn (ProjectStage $stage) => [$stage->value => $stage->label()]),
        ]);
    }
}
