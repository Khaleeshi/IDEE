<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\ProposeMatchRequest;
use App\Models\Project;
use App\Models\ProjectMatch;
use App\Services\Projects\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rapprochement entre projets similaires (PR-08, RG-12).
 */
class MatchingController extends Controller
{
    public function __construct(private readonly MatchingService $matching) {}

    public function index(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.matches', [
            'project' => $project,
            'suggestions' => $this->matching->suggestions($project),
            ...$this->matching->proposalsFor($project),
        ]);
    }

    public function toggle(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->matching->setOpen($project, $request->boolean('open'));

        return redirect()->route('matches.index', $project)->with('status', $project->open_to_matching
            ? 'Rapprochements activés : vous verrez les projets proches dont le porteur les accepte aussi.'
            : 'Rapprochements désactivés : votre projet n\'est plus proposé.');
    }

    public function store(ProposeMatchRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->matching->propose($project, Project::findOrFail($request->validated('target_project_id')), $request->user(), $request->validated('message'));

        return redirect()->route('matches.index', $project)->with('status', 'Proposition envoyée. Une conversation s\'ouvrira si l\'autre porteur accepte.');
    }

    public function respond(DecisionRequest $request, ProjectMatch $projectMatch): RedirectResponse
    {
        Gate::authorize('respond', $projectMatch);

        $match = $this->matching->respond($projectMatch, $request->boolean('accepted'));

        return $match->contactRequest
            ? redirect()->route('contact-requests.show', $match->contactRequest)->with('status', 'Rapprochement accepté : vous pouvez échanger avec l\'autre porteur.')
            : redirect()->route('matches.index', $match->targetProject)->with('status', 'Rapprochement décliné.');
    }
}
