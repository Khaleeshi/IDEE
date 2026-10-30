<?php

namespace App\Http\Controllers\Web;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Projects\ProjectSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(Request $request, ProjectSearch $search): View
    {
        $filters = $request->only(['q', 'sector', 'region', 'stage', 'need_type']);

        return view('feed.index', [
            'projects' => $search->query($filters)->paginate(15)->withQueryString(),
            'filters' => $filters,
            'sectors' => Sector::options('slug'),
            'needTypes' => collect(NeedType::cases())->mapWithKeys(fn (NeedType $type) => [$type->value => $type->label()]),
            'stages' => collect(ProjectStage::cases())->mapWithKeys(fn (ProjectStage $stage) => [$stage->value => $stage->label()]),
        ]);
    }
}
