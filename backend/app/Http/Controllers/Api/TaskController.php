<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\MemberResource;
use App\Http\Resources\ProjectTaskResource;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\Collaboration\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Tableau de tâches partagé du projet (CO-02).
 */
class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('manageTasks', $project);

        return ProjectTaskResource::collection(collect($this->tasks->board($project))->flatten(1))
            ->additional(['members' => MemberResource::collection($this->tasks->members($project)->load('roleAssignments'))]);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('manageTasks', $project);

        $task = $this->tasks->create(
            $project,
            $request->user(),
            $request->validated('title'),
            $request->filled('due_on') ? Carbon::parse($request->validated('due_on')) : null,
            $request->filled('assignee_id') ? User::findOrFail($request->validated('assignee_id')) : null,
        );

        return (new ProjectTaskResource($task->load('assignee')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function move(MoveTaskRequest $request, ProjectTask $projectTask): ProjectTaskResource
    {
        Gate::authorize('update', $projectTask);

        return new ProjectTaskResource($this->tasks->move($projectTask, TaskStatus::from($request->validated('status')))->load('assignee'));
    }

    public function destroy(ProjectTask $projectTask): Response
    {
        Gate::authorize('delete', $projectTask);

        $projectTask->delete();

        return response()->noContent();
    }
}
