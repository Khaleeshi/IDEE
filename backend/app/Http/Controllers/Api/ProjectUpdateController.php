<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectUpdateRequest;
use App\Http\Resources\ProjectUpdateResource;
use App\Models\Project;
use App\Services\Projects\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectUpdateController extends Controller
{
    /**
     * Journal de progression d'un projet (PR-04).
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        abort_unless(Gate::forUser(request()->user('sanctum'))->allows('view', $project), Response::HTTP_NOT_FOUND);

        return ProjectUpdateResource::collection($project->updates()->paginate(20));
    }

    public function store(StoreProjectUpdateRequest $request, Project $project, ProjectService $projects): JsonResponse
    {
        Gate::authorize('update', $project);

        $update = $projects->publishUpdate($project, $request->user(), $request->validated('body'));

        return (new ProjectUpdateResource($update))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
