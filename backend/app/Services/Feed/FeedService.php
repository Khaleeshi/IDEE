<?php

namespace App\Services\Feed;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Collaboration;
use App\Models\Need;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Services\Network\FollowService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fil d'activité du réseau (FC-01) : nouveaux projets, appels à action,
 * mises à jour et réussites.
 */
class FeedService
{
    public function record(ActivityType $type, Project $project, User $actor, ?Need $need = null, ?ProjectUpdate $update = null, ?Collaboration $collaboration = null): Activity
    {
        $activity = new Activity;
        $activity->type = $type;
        $activity->actor()->associate($actor);
        $activity->project()->associate($project);
        $activity->need()->associate($need);
        $activity->projectUpdate()->associate($update);
        $activity->collaboration()->associate($collaboration);
        $activity->save();

        return $activity;
    }

    public function __construct(private readonly FollowService $follows) {}

    /**
     * Activités des projets actifs, les plus récentes d'abord. Avec un membre,
     * seulement ce qui touche ses projets, personnes et secteurs suivis (FC-02).
     *
     * @return Builder<Activity>
     */
    public function query(?User $followedBy = null): Builder
    {
        $followed = $followedBy ? $this->follows->followedIds($followedBy) : null;

        return Activity::query()
            ->whereHas('project', fn (Builder $query) => $query->active())
            ->when($followed !== null, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereIn('project_id', $followed['projects'])
                ->orWhereIn('actor_id', $followed['users'])
                ->orWhereHas('project', fn (Builder $query) => $query->whereIn('sector_id', $followed['sectors']))))
            ->with(['actor.roleAssignments', 'project.sector', 'need', 'projectUpdate', 'collaboration.declarer', 'collaboration.partner', 'collaboration.project'])
            ->latest()
            ->latest('id');
    }
}
