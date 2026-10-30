<?php

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\Collaboration\TaskService;

class ProjectPolicy
{
    public function __construct(private readonly TaskService $tasks) {}

    /**
     * Un projet archivé n'est plus visible que de son porteur.
     */
    public function view(?User $user, Project $project): bool
    {
        return $project->status !== ProjectStatus::Archive || $project->isOwnedBy($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Tableau de tâches : le porteur et ses partenaires confirmés (CO-02).
     */
    public function manageTasks(User $user, Project $project): bool
    {
        return $this->tasks->canManage($project, $user);
    }
}
