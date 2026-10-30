<?php

namespace App\Http\Controllers\Web;

use App\Enums\CollaborationStatus;
use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreProjectUpdateRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\JourneyStep;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Sector;
use App\Services\Assistant\AssistantService;
use App\Services\Collaboration\TaskService;
use App\Services\ContactRequests\ReactivityIndicator;
use App\Services\Documents\DocumentService;
use App\Services\Journeys\JourneyService;
use App\Services\Network\FollowService;
use App\Services\Projects\ProjectService;
use App\Services\Projects\ReadinessScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function show(Request $request, Project $project, ReadinessScore $readiness, ReactivityIndicator $reactivity, JourneyService $journeys, FollowService $follows, DocumentService $documents, AssistantService $assistant, TaskService $tasks): View
    {
        abort_unless(Gate::allows('view', $project), Response::HTTP_NOT_FOUND);

        $isOwner = $project->isOwnedBy($request->user());

        $project->load(['owner.roleAssignments', 'sector']);
        $needs = $isOwner
            ? $project->needs()->with('helpOffers.user')->latest()->get()
            : $project->openNeeds()->latest()->get();

        return view('projects.show', [
            'project' => $project,
            'needs' => $needs,
            'isOwner' => $isOwner,
            'readiness' => $isOwner ? $readiness->evaluate($project) : null,
            'reactivity' => $reactivity->for($project->owner),
            'updates' => $project->updates()->limit(10)->get(),
            'team' => $project->team,
            // Réussites confirmées par les deux parties (FC-06) et tableau de tâches partagé (CO-02).
            'collaborations' => $project->collaborations()->where('status', CollaborationStatus::Confirmee)->with(['declarer', 'partner'])->latest('confirmed_at')->limit(10)->get(),
            'canManageTasks' => $tasks->canManage($project, $request->user()),
            'following' => $follows->isFollowing($request->user(), $project),
            // Documents privés : visibles seulement par le porteur et les membres autorisés (RG-05).
            'documents' => $documents->canRead($request->user(), $project) ? $project->documents : null,
            // PR-07 : parts envisagées réservées au porteur et aux lecteurs liés par l'accord de confidentialité.
            'showShares' => $documents->canRead($request->user(), $project),
            'documentsCount' => $project->documents()->count(),
            'documentAccess' => $documents->accessOf($request->user(), $project),
            'accesses' => $isOwner ? $project->documentAccesses()->with('user')->latest()->get() : collect(),
            'documentLogs' => $isOwner ? $documents->logFor($project) : collect(),
            // Assistant IA, réservé au porteur (section 7).
            'assistantEnabled' => $assistant->isEnabled(),
            'clarification' => $isOwner ? IdeaClarification::where('project_id', $project->id)->latest()->first() : null,
            'latestPlan' => $isOwner ? BusinessPlan::where('project_id', $project->id)->orderByDesc('version')->first() : null,
            'needTypes' => $this->needTypes(),
            // Demande d'aide lancée depuis une étape de parcours guidé.
            'needDraft' => $isOwner && ($step = JourneyStep::find($request->string('etape')->toString()))
                ? $journeys->needDraft($step)
                : null,
        ]);
    }

    public function publishUpdate(StoreProjectUpdateRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->projects->publishUpdate($project, $request->user(), $request->validated('body'));

        return redirect()->to(route('projects.show', $project).'#journal')->with('status', 'Mise à jour publiée dans le fil.');
    }

    public function addTeamMember(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->projects->addTeamMember($project, $request->validated());

        return redirect()->to(route('projects.show', $project).'#equipe')->with('status', 'Équipe mise à jour.');
    }

    public function updateTeamMember(UpdateProjectMemberRequest $request, ProjectMember $projectMember): RedirectResponse
    {
        Gate::authorize('update', $projectMember->project);

        $this->projects->updateTeamMember($projectMember, $request->validated());

        return redirect()->to(route('projects.show', $projectMember->project).'#equipe')->with('status', 'Équipe mise à jour.');
    }

    public function removeTeamMember(ProjectMember $projectMember): RedirectResponse
    {
        Gate::authorize('update', $projectMember->project);

        $this->projects->removeTeamMember($projectMember);

        return redirect()->to(route('projects.show', $projectMember->project).'#equipe')->with('status', 'Personne retirée de l\'équipe.');
    }

    public function mine(Request $request): View
    {
        return view('projects.mine', [
            'projects' => $request->user()->projects()->with(['sector', 'openNeeds'])->latest('last_activity_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.form', ['project' => new Project, ...$this->formOptions()]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projects->create($request->user(), $request->projectAttributes(), $request->firstNeed());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Votre projet est publié. Ajoutez maintenant ce qui lui manque : ses appels à action.');
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.form', ['project' => $project, ...$this->formOptions()]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->projects->update($project, $request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Projet mis à jour.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.mine')->with('status', 'Projet supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'needTypes' => $this->needTypes(),
            'sectors' => Sector::options(),
            'stages' => collect(ProjectStage::cases())->mapWithKeys(fn (ProjectStage $stage) => [$stage->value => $stage->label()]),
        ];
    }

    /**
     * @return Collection<string, string>
     */
    private function needTypes()
    {
        return collect(NeedType::cases())->mapWithKeys(fn (NeedType $type) => [$type->value => $type->label()]);
    }
}
