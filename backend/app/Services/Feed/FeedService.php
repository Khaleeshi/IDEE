<?php

namespace App\Services\Feed;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fil d'activité du réseau (FC-01) : nouveaux projets, appels à action,
 * mises à jour et réussites.
 */
class FeedService
{
    public function record(ActivityType $type, Project $project, User $actor, ?Need $need = null, ?ProjectUpdate $update = null): Activity
    {
        $activity = new Activity;
        $activity->type = $type;
        $activity->actor()->associate($actor);
        $activity->project()->associate($project);
        $activity->need()->associate($need);
        $activity->projectUpdate()->associate($update);
        $activity->save();

        return $activity;
    }

    /**
     * Activités des projets actifs, les plus récentes d'abord.
     *
     * @return Builder<Activity>
     */
    public function query(): Builder
    {
        return Activity::query()
            ->whereHas('project', fn (Builder $query) => $query->active())
            ->with(['actor.roleAssignments', 'project.sector', 'need', 'projectUpdate'])
            ->latest()
            ->latest('id');
    }
}
