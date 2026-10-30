<?php

namespace App\Http\Controllers\Web;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Feed\FeedService;
use App\Services\Network\FollowService;
use App\Services\Network\RecommendationService;
use App\Services\Projects\ProjectSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    /**
     * Accueil : le fil d'activité (FC-01), ou la liste des projets dès qu'une recherche est lancée (RR-01).
     */
    public function index(Request $request, ProjectSearch $search, FeedService $feed, FollowService $follows, RecommendationService $recommendations): View
    {
        $user = $request->user();
        $filters = array_filter($request->only(ProjectSearch::FILTERS), fn ($value) => $value !== null && $value !== '');
        $tab = match (true) {
            $filters !== [] || $request->query('vue') === 'projets' => 'projets',
            $user !== null && $request->query('vue') === 'suivis' => 'suivis',
            default => 'activite',
        };
        $sector = isset($filters['sector']) ? Sector::firstWhere('slug', $filters['sector']) : null;

        return view('feed.index', [
            'tab' => $tab,
            'projects' => $tab === 'projets' ? $search->query($filters)->paginate(15)->withQueryString() : null,
            'activities' => $tab !== 'projets' ? $feed->query($tab === 'suivis' ? $user : null)->paginate(20)->withQueryString() : null,
            'recommendations' => $user && $tab === 'activite' ? $recommendations->forUser($user) : collect(),
            'sector' => $sector,
            'followingSector' => $sector ? $follows->isFollowing($user, $sector) : false,
            'filters' => $filters,
            'sectors' => Sector::options('slug'),
            'needTypes' => collect(NeedType::cases())->mapWithKeys(fn (NeedType $type) => [$type->value => $type->label()]),
            'stages' => collect(ProjectStage::cases())->mapWithKeys(fn (ProjectStage $stage) => [$stage->value => $stage->label()]),
        ]);
    }
}
