<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
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
            ->query($request->only(['q', 'sector', 'region', 'stage', 'need_type']))
            ->paginate(20)
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->user(), $request->validated());

        return (new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        abort_unless(Gate::forUser(request()->user('sanctum'))->allows('view', $project), Response::HTTP_NOT_FOUND);

        return new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds']));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        Gate::authorize('update', $project);

        $project = $this->projects->update($project, $request->validated());

        return new ProjectResource($project->load(['owner.roleAssignments', 'sector', 'openNeeds']));
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
