<?php

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
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
}
