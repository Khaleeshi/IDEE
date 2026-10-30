<?php

namespace App\Http\Controllers\Web;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\Collaboration\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tableau de tâches partagé du projet (CO-02).
 */
class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request, Project $project): View
    {
        Gate::authorize('manageTasks', $project);

        return view('projects.tasks', [
            'project' => $project,
            'board' => $this->tasks->board($project),
            'members' => $this->tasks->members($project),
            'statuses' => TaskStatus::cases(),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('manageTasks', $project);

        $this->tasks->create(
            $project,
            $request->user(),
            $request->validated('title'),
            $request->filled('due_on') ? Carbon::parse($request->validated('due_on')) : null,
            $request->filled('assignee_id') ? User::findOrFail($request->validated('assignee_id')) : null,
        );

        return redirect()->route('tasks.index', $project)->with('status', 'Tâche ajoutée.');
    }

    public function move(MoveTaskRequest $request, ProjectTask $projectTask): RedirectResponse
    {
        Gate::authorize('update', $projectTask);

        $this->tasks->move($projectTask, TaskStatus::from($request->validated('status')));

        return redirect()->route('tasks.index', $projectTask->project)->with('status', 'Tâche déplacée dans « '.$projectTask->status->label().' ».');
    }

    public function destroy(ProjectTask $projectTask): RedirectResponse
    {
        Gate::authorize('delete', $projectTask);

        $project = $projectTask->project;
        $projectTask->delete();

        return redirect()->route('tasks.index', $project)->with('status', 'Tâche retirée du tableau.');
    }
}
