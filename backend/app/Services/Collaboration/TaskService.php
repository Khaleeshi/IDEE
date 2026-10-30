<?php

namespace App\Services\Collaboration;

use App\Enums\AgreementStatus;
use App\Enums\CollaborationStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Collaboration;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Tableau de tâches partagé d'un projet (CO-02), ouvert au porteur et aux
 * personnes avec qui il a conclu une prochaine étape ou une collaboration.
 */
class TaskService
{
    /**
     * Porteur du projet et partenaires.
     *
     * @return Collection<int, User>
     */
    public function members(Project $project): Collection
    {
        $ids = Agreement::query()
            ->where('project_id', $project->id)
            ->whereIn('status', [AgreementStatus::Acceptee, AgreementStatus::Realisee])
            ->get(['proposer_id', 'owner_id'])
            ->flatMap(fn (Agreement $agreement) => [$agreement->proposer_id, $agreement->owner_id])
            ->merge(Collaboration::query()
                ->where('project_id', $project->id)
                ->where('status', CollaborationStatus::Confirmee)
                ->get(['declared_by', 'partner_id'])
                ->flatMap(fn (Collaboration $collaboration) => [$collaboration->declared_by, $collaboration->partner_id]))
            ->push($project->owner_id)
            ->unique();

        return User::query()->whereIn('id', $ids)->orderBy('name')->get()
            ->sortByDesc(fn (User $user) => $user->id === $project->owner_id)
            ->values();
    }

    public function canManage(Project $project, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $project->isOwnedBy($user) || $this->members($project)->contains('id', $user->id);
    }

    /**
     * @throws ValidationException
     */
    public function create(Project $project, User $author, string $title, ?Carbon $dueOn = null, ?User $assignee = null): ProjectTask
    {
        if ($assignee !== null && ! $this->members($project)->contains('id', $assignee->id)) {
            throw ValidationException::withMessages(['assignee_id' => 'La tâche ne peut être confiée qu\'au porteur ou à un partenaire du projet.']);
        }

        $task = new ProjectTask(['title' => trim($title), 'due_on' => $dueOn]);
        $task->project()->associate($project);
        $task->author()->associate($author);
        $task->assignee()->associate($assignee);
        $task->status = TaskStatus::AFaire;
        $task->save();

        return $task;
    }

    /**
     * Change la colonne d'une tâche ; l'étape convenue qui l'a créée suit le même état.
     */
    public function move(ProjectTask $task, TaskStatus $status): ProjectTask
    {
        $task->status = $status;
        $task->done_at = $status === TaskStatus::Fait ? ($task->done_at ?? now()) : null;
        $task->save();

        $agreement = $task->agreement;

        if ($agreement !== null && in_array($agreement->status, [AgreementStatus::Acceptee, AgreementStatus::Realisee], true)) {
            $done = $status === TaskStatus::Fait;
            $agreement->status = $done ? AgreementStatus::Realisee : AgreementStatus::Acceptee;
            $agreement->done_at = $done ? ($agreement->done_at ?? now()) : null;
            $agreement->save();
        }

        return $task;
    }

    /**
     * Tâches du projet par colonne, les échéances les plus proches d'abord.
     *
     * @return array<string, Collection<int, ProjectTask>>
     */
    public function board(Project $project): array
    {
        $tasks = $project->tasks()
            ->with('assignee')
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->latest()
            ->get();

        return collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status) => [$status->value => $tasks->where('status', $status)->values()])
            ->all();
    }
}
