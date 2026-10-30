<?php

namespace App\Http\Controllers\Web;

use App\Enums\ApplicationStatus;
use App\Enums\OpportunityKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrackApplicationRequest;
use App\Models\Opportunity;
use App\Services\Opportunities\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunities) {}

    public function index(Request $request): View
    {
        $kind = OpportunityKind::tryFrom((string) $request->query('type'));

        return view('opportunities.index', [
            'opportunities' => $this->opportunities->search($kind, $request->query('q'))->get(),
            'kinds' => OpportunityKind::cases(),
            'kind' => $kind,
            'applications' => $request->user() ? $this->opportunities->applicationsOf($request->user()) : collect(),
        ]);
    }

    public function show(Request $request, Opportunity $opportunity): View
    {
        abort_unless($opportunity->is_published, 404);

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'application' => $this->opportunities->applicationOf($request->user(), $opportunity),
            'statuses' => ApplicationStatus::cases(),
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
