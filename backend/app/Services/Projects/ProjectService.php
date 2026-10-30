<?php

namespace App\Services\Projects;

use App\Enums\ActivityType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Services\Feed\FeedService;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Champs dont la modification crée une nouvelle version horodatée.
     */
    private const VERSIONED_FIELDS = ['title', 'summary', 'details', 'stage', 'region', 'sector_id'];

    public function __construct(
        private readonly ReadinessScore $readiness,
        private readonly FeedService $feed,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $owner, array $attributes): Project
    {
        return DB::transaction(function () use ($owner, $attributes) {
            $project = new Project($attributes);
            $project->owner()->associate($owner);
            $project->status = ProjectStatus::Actif;
            $project->last_activity_at = now();
            $project->save();

            if (! $owner->hasRole(UserRole::Porteur)) {
                $owner->syncRoles([...$owner->roles(), UserRole::Porteur]);
            }

            $this->recordVersion($project);
            $this->readiness->refresh($project);
            $this->feed->record(ActivityType::ProjetPublie, $project, $owner);

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        return DB::transaction(function () use ($project, $attributes) {
            $project->fill($attributes);
            $versioned = $project->isDirty(self::VERSIONED_FIELDS);

            $project->recordActivity();

            if ($versioned) {
                $this->recordVersion($project);
            }

            $this->readiness->refresh($project);

            return $project;
        });
    }

    /**
     * Publie une mise à jour datée dans le journal de progression (PR-04).
     */
    public function publishUpdate(Project $project, User $author, string $body): ProjectUpdate
    {
        $update = new ProjectUpdate(['body' => $body]);
        $update->project()->associate($project);
        $update->author()->associate($author);
        $update->save();

        $project->recordActivity();
        $this->feed->record(ActivityType::MiseAJour, $project, $author, update: $update);

        return $update;
    }

    /**
     * Scelle l'état du projet par une empreinte SHA-256 datée.
     */
    public function recordVersion(Project $project): void
    {
        $content = [
            'title' => $project->title,
            'summary' => $project->summary,
            'details' => $project->details,
            'stage' => $project->stage?->value,
            'region' => $project->region,
            'sector_id' => $project->sector_id,
            'recorded_at' => now()->toIso8601String(),
        ];

        $project->versions()->create([
            'content' => $content,
            'fingerprint' => hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ]);
    }

    /**
     * Met en sommeil les projets sans activité depuis le délai fixé (RG-07).
     */
    public function markDormantProjects(): int
    {
        return Project::query()
            ->where('status', ProjectStatus::Actif)
            ->where('last_activity_at', '<', now()->subDays(config('idee.dormant_after_days')))
            ->update(['status' => ProjectStatus::EnSommeil]);
    }
}
