<?php

namespace App\Http\Controllers\Web;

use App\Enums\ApplicationStatus;
use App\Enums\OpportunityKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrackApplicationRequest;
use App\Models\Opportunity;
use App\Models\Project;
use App\Services\Opportunities\EligibilityService;
use App\Services\Opportunities\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly OpportunityService $opportunities,
        private readonly EligibilityService $eligibility,
    ) {}

    public function index(Request $request): View
    {
        $kind = OpportunityKind::tryFrom((string) $request->query('type'));
        $myProjects = $request->user()?->projects()->active()->latest('last_activity_at')->get() ?? collect();
        // OP-02 : seulement les opportunités compatibles avec le projet choisi.
        $project = $myProjects->firstWhere('id', $request->query('projet'));
        $opportunities = $this->opportunities->search($kind, $request->query('q'))->get();

        return view('opportunities.index', [
            'opportunities' => $project ? $this->eligibility->eligibleFor($project, $opportunities->toBase()) : $opportunities,
            'kinds' => OpportunityKind::cases(),
            'kind' => $kind,
            'myProjects' => $myProjects,
            'project' => $project,
            'applications' => $request->user() ? $this->opportunities->applicationsOf($request->user()) : collect(),
        ]);
    }

    public function show(Request $request, Opportunity $opportunity): View
    {
        abort_unless($opportunity->is_published, 404);

        $projects = $request->user()?->projects()->active()->with(['sector', 'openNeeds'])->get() ?? collect();

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'application' => $this->opportunities->applicationOf($request->user(), $opportunity),
            'statuses' => ApplicationStatus::cases(),
            'eligibilities' => $projects->map(fn (Project $project) => ['project' => $project, ...$this->eligibility->check($opportunity, $project)]),
        ]);
    }

    public function track(TrackApplicationRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $this->opportunities->track(
            $request->user(),
            $opportunity,
            ApplicationStatus::from($request->validated('status')),
            $request->validated('note'),
            $request->validated('remind_on'),
        );

        return redirect()->route('opportunities.show', $opportunity)->with('status', 'Candidature enregistrée dans votre suivi.');
    }

    public function forget(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $this->opportunities->forget($request->user(), $opportunity);

        return redirect()->route('opportunities.show', $opportunity)->with('status', 'Candidature retirée de votre suivi.');
    }
}
