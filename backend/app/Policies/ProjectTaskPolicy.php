<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;
use App\Services\Collaboration\TaskService;

class ProjectTaskPolicy
{
    public function __construct(private readonly TaskService $tasks) {}

    public function update(User $user, ProjectTask $task): bool
    {
        return $this->tasks->canManage($task->project, $user);
    }

    /**
     * Le porteur et l'auteur de la tâche peuvent la retirer du tableau.
     */
    public function delete(User $user, ProjectTask $task): bool
    {
        return $task->project->isOwnedBy($user) || $task->created_by === $user->getKey();
    }
}
