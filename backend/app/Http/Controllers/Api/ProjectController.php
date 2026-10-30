<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Projects\ProjectSearch;
use App\Services\Projects\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    public function index(Request $request, ProjectSearch $search): AnonymousResourceCollection
    {
        $projects = $search
            ->query($request->only(['q', 'sector', 'region', 'stage', 'need_type', 'amount_min', 'amount_max']))
            ->paginate(20)
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->user(), $request->projectAttributes(), $request->firstNeed());

        return (new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        abort_unless(Gate::forUser(request()->user('sanctum'))->allows('view', $project), Response::HTTP_NOT_FOUND);

        return new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds', 'team']));
    }

    /**
     * Ajoute une personne à l'équipe du projet (PR-02).
     */
    public function addTeamMember(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $member = $this->projects->addTeamMember($project, $request->validated());

        return response()->json(['data' => $this->teamMember($member)], Response::HTTP_CREATED);
    }

    /**
     * Rôle et part envisagée d'une personne de l'équipe (PR-07).
     */
    public function updateTeamMember(UpdateProjectMemberRequest $request, ProjectMember $projectMember): JsonResponse
    {
        Gate::authorize('update', $projectMember->project);

        return response()->json(['data' => $this->teamMember($this->projects->updateTeamMember($projectMember, $request->validated()))]);
    }

    public function removeTeamMember(ProjectMember $projectMember): Response
    {
        Gate::authorize('update', $projectMember->project);

        $this->projects->removeTeamMember($projectMember);

        return response()->noContent();
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        Gate::authorize('update', $project);

        $project = $this->projects->update($project, $request->validated());

        return new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds', 'team']));
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function teamMember(ProjectMember $member): array
    {
        return ['id' => $member->id, 'name' => $member->name, 'role' => $member->role, 'share_percent' => $member->share_percent];
    }
}
