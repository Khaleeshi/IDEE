<?php

namespace App\Services\Projects;

use App\Enums\ContactRequestStatus;
use App\Enums\ProjectMatchStatus;
use App\Enums\ProjectStatus;
use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Notifications\ProjectMatchNotice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rapprochement entre projets similaires (PR-08). RG-12 : il n'est proposé
 * qu'entre porteurs qui l'ont activé, ne montre que la fiche publique, et
 * n'ouvre une conversation qu'avec l'accord des deux.
 */
class MatchingService
{
    /**
     * Mots trop courants pour rapprocher deux projets.
     */
    private const COMMON_WORDS = [
        'projet', 'projets', 'notre', 'votre', 'nous', 'vous', 'leurs', 'avec', 'pour', 'dans', 'sans',
        'entre', 'depuis', 'cette', 'faire', 'aussi', 'plus', 'tres', 'sont', 'senegal', 'dakar',
        'produit', 'produits', 'service', 'services', 'clients', 'besoin', 'activite', 'entreprise',
    ];

    public function setOpen(Project $project, bool $open): Project
    {
        $project->forceFill(['open_to_matching' => $open])->save();

        return $project;
    }

    /**
     * Projets proches parmi ceux dont le porteur accepte les rapprochements,
     * chacun avec les raisons du rapprochement.
     *
     * @return Collection<int, Project>
     */
    public function suggestions(Project $project): Collection
    {
        if (! $project->open_to_matching) {
            return collect();
        }

        $paired = ProjectMatch::query()
            ->where('project_id', $project->id)->pluck('target_project_id')
            ->merge(ProjectMatch::query()->where('target_project_id', $project->id)->pluck('project_id'));
        $words = $this->keywords($project);

        return Project::query()
            ->active()
            ->where('open_to_matching', true)
            ->whereKeyNot($project->id)
            ->where('owner_id', '!=', $project->owner_id)
            ->whereNotIn('id', $paired)
            ->with(['sector', 'owner'])
            ->latest('last_activity_at')
            ->limit(500)
            ->get()
            ->map(function (Project $candidate) use ($project, $words) {
                $reasons = [];
                $score = 0;

                if ($project->sector_id !== null && $candidate->sector_id === $project->sector_id) {
                    $reasons[] = 'Même secteur : '.$candidate->sector->name;
                    $score += 3;
                }

                if (filled($project->region) && Str::lower((string) $candidate->region) === Str::lower($project->region)) {
                    $reasons[] = 'Même région : '.$candidate->region;
                    $score += 1;
                }

                $shared = $words->intersect($this->keywords($candidate))->take(3)->values();

                if ($shared->isNotEmpty()) {
                    $reasons[] = 'Mots en commun : '.$shared->join(', ');
                    $score += $shared->count();
                }

                return $candidate->setAttribute('match_reasons', $reasons)->setAttribute('match_score', $score);
            })
            ->filter(fn (Project $candidate) => $candidate->match_score >= 3)
            ->sortByDesc('match_score')
            ->take(config('idee.matching.suggestions'))
            ->values();
    }

    /**
     * @throws ValidationException
     */
    public function propose(Project $project, Project $target, User $requester, ?string $message = null): ProjectMatch
    {
        if (! $project->open_to_matching || ! $target->open_to_matching) {
            throw ValidationException::withMessages(['target_project_id' => 'Les deux porteurs doivent avoir activé les rapprochements.']);
        }

        if ($target->owner_id === $project->owner_id || $target->status !== ProjectStatus::Actif) {
            throw ValidationException::withMessages(['target_project_id' => 'Ce projet ne peut pas vous être rapproché.']);
        }

        $exists = ProjectMatch::query()
            ->where(fn ($query) => $query->where('project_id', $project->id)->where('target_project_id', $target->id))
            ->orWhere(fn ($query) => $query->where('project_id', $target->id)->where('target_project_id', $project->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['target_project_id' => 'Un rapprochement a déjà été proposé entre ces deux projets.']);
        }

        $match = new ProjectMatch(['message' => filled($message) ? trim($message) : null]);
        $match->project()->associate($project);
        $match->targetProject()->associate($target);
        $match->requester()->associate($requester);
        $match->status = ProjectMatchStatus::Proposee;
        $match->save();

        $target->owner->notify(new ProjectMatchNotice(
            $target,
            $requester->name.' propose de rapprocher son projet « '.$project->title.' » du vôtre, « '.$target->title.' ».',
        ));

        return $match;
    }

    /**
     * Le porteur du projet visé accepte ou décline. Accepté, le rapprochement
     * ouvre une conversation entre les deux porteurs, déjà acceptée des deux côtés.
     *
     * @throws ValidationException
     */
    public function respond(ProjectMatch $match, bool $accepted): ProjectMatch
    {
        if ($match->status !== ProjectMatchStatus::Proposee) {
            throw ValidationException::withMessages(['match' => 'Ce rapprochement a déjà reçu une réponse.']);
        }

        DB::transaction(function () use ($match, $accepted) {
            $match->status = $accepted ? ProjectMatchStatus::Acceptee : ProjectMatchStatus::Refusee;
            $match->responded_at = now();

            if ($accepted) {
                $conversation = new ContactRequest([
                    'project_id' => $match->target_project_id,
                    'message' => 'Rapprochement accepté entre « '.$match->project->title.' » et « '.$match->targetProject->title.' ».'
                        .($match->message ? "\n\n".$match->message : ''),
                    'expires_at' => now(),
                ]);
                $conversation->forceFill([
                    'sender_id' => $match->requested_by,
                    'recipient_id' => $match->targetProject->owner_id,
                    'status' => ContactRequestStatus::Interesse,
                    'read_at' => now(),
                    'responded_at' => now(),
                ])->save();

                $match->contactRequest()->associate($conversation);
            }

            $match->save();
        });

        $match->requester->notify(new ProjectMatchNotice(
            $match->project,
            $match->targetProject->owner->name.($accepted ? ' a accepté' : ' a décliné').' le rapprochement avec « '.$match->targetProject->title.' ».',
        ));

        return $match;
    }

    /**
     * Propositions reçues et envoyées pour un projet.
     *
     * @return array{received: Collection<int, ProjectMatch>, sent: Collection<int, ProjectMatch>}
     */
    public function proposalsFor(Project $project): array
    {
        return [
            'received' => ProjectMatch::where('target_project_id', $project->id)->with(['project.sector', 'requester'])->latest()->get(),
            'sent' => ProjectMatch::where('project_id', $project->id)->with(['targetProject.sector', 'targetProject.owner'])->latest()->get(),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function keywords(Project $project): Collection
    {
        return Str::of($project->title.' '.$project->summary)
            ->ascii()
            ->lower()
            ->explode(' ')
            ->map(fn (string $word) => preg_replace('/[^a-z]/', '', $word))
            ->filter(fn (string $word) => strlen($word) >= 5 && ! in_array($word, self::COMMON_WORDS, true))
            ->map(fn (string $word) => rtrim($word, 's'))
            ->unique()
            ->values();
    }
}
