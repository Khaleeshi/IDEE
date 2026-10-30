<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\Projects\ProjectService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    /**
     * Recherche de projets actifs (RR-01) : mot-clé, secteur, région, stade, type de besoin.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->active()
            ->with(['owner.roleAssignments', 'sector', 'openNeeds'])
            ->when($request->string('q')->trim()->value(), function (Builder $query, string $search) {
                $query->where(fn (Builder $query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%"));
            })
            ->when($request->query('sector'), fn (Builder $query, $sector) => $query->whereHas(
                'sector', fn (Builder $query) => $query->where('slug', $sector)
            ))
            ->when($request->query('region'), fn (Builder $query, $region) => $query->where('region', $region))
            ->when($request->query('stage'), fn (Builder $query, $stage) => $query->where('stage', $stage))
            ->when($request->query('need_type'), fn (Builder $query, $type) => $query->whereHas(
                'openNeeds', fn (Builder $query) => $query->where('type', $type)
            ))
            ->latest('last_activity_at')
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
