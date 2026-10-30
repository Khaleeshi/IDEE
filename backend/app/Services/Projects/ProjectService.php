<?php

namespace App\Services\Projects;

use App\Enums\ActivityType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Services\Feed\FeedService;
use App\Services\Needs\NeedService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    /**
     * Champs dont la modification crée une nouvelle version horodatée.
     */
    private const VERSIONED_FIELDS = ['title', 'summary', 'details', 'stage', 'region', 'sector_id'];

    public function __construct(
        private readonly ReadinessScore $readiness,
        private readonly FeedService $feed,
        private readonly NeedService $needs,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array{type: string, title: string, description: string}|null  $firstNeed  premier appel à action (PR-01)
     */
    public function create(User $owner, array $attributes, ?array $firstNeed = null): Project
    {
        return DB::transaction(function () use ($owner, $attributes, $firstNeed) {
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

            if ($firstNeed !== null) {
                $this->needs->create($project, $firstNeed);
            }

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
     * Ajoute une personne à l'équipe affichée sur la fiche (PR-02).
     *
     * @param  array{name: string, role: string}  $attributes
     *
     * @throws ValidationException
     */
    public function addTeamMember(Project $project, array $attributes): ProjectMember
    {
        if ($project->team()->count() >= config('idee.max_team_members')) {
            throw ValidationException::withMessages(['name' => 'L\'équipe affichée compte au plus '.config('idee.max_team_members').' personnes.']);
        }

        $this->ensureSharesFit($project, $attributes['share_percent'] ?? null);

        $member = $project->team()->create([...$attributes, 'position' => $project->team()->count() + 1]);
        $project->recordActivity();
        $this->readiness->refresh($project);

        return $member;
    }

    /**
     * Modifie le rôle ou la part envisagée d'une personne de l'équipe (PR-07).
     *
     * @param  array{role?: string, share_percent?: float|string|null}  $attributes
     *
     * @throws ValidationException
     */
    public function updateTeamMember(ProjectMember $member, array $attributes): ProjectMember
    {
        if (array_key_exists('share_percent', $attributes)) {
            $this->ensureSharesFit($member->project, $attributes['share_percent'], $member);
        }

        $member->update($attributes);

        return $member;
    }

    /**
     * Les parts envisagées de l'équipe ne dépassent pas 100 % au total.
     *
     * @throws ValidationException
     */
    private function ensureSharesFit(Project $project, float|string|null $share, ?ProjectMember $except = null): void
    {
        if ($share === null || $share === '') {
            return;
        }

        $others = (float) $project->team()->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))->sum('share_percent');

        if ($others + (float) $share > 100) {
            throw ValidationException::withMessages([
                'share_percent' => 'Les parts envisagées dépassent 100 % au total : il reste '.max(0, 100 - $others).' % à répartir.',
            ]);
        }
    }

    public function removeTeamMember(ProjectMember $member): void
    {
        $project = $member->project;
        $member->delete();
        $this->readiness->refresh($project);
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
