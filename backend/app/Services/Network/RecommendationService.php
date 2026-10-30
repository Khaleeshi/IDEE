<?php

namespace App\Services\Network;

use App\Enums\NeedStatus;
use App\Enums\NeedType;
use App\Enums\UserRole;
use App\Models\Need;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Recommandations quotidiennes d'appels à action selon le profil (RR-02),
 * chacune accompagnée des raisons qui l'expliquent (RR-03).
 */
class RecommendationService
{
    private const HELPER_NEEDS = [NeedType::Competence, NeedType::Mentor, NeedType::Associe];

    public function __construct(private readonly FollowService $follows) {}

    /**
     * @return Collection<int, array{need: Need, reasons: list<string>}>
     */
    public function forUser(User $user): Collection
    {
        if (! $user->wantsPersonalizedRecommendations()) {
            return collect();
        }

        // Calculées une fois par jour, puis relues tant qu'elles restent ouvertes.
        $picks = Cache::remember(
            "recommandations:{$user->id}:".today()->toDateString(),
            now()->endOfDay(),
            fn () => $this->compute($user)->map(fn (array $pick) => [
                'need_id' => $pick['need']->id,
                'reasons' => $pick['reasons'],
            ])->all(),
        );

        $needs = Need::whereIn('id', array_column($picks, 'need_id'))
            ->where('status', NeedStatus::Ouvert)
            ->with('project.sector')
            ->get()
            ->keyBy('id');

        return collect($picks)
            ->filter(fn (array $pick) => $needs->has($pick['need_id']))
            ->map(fn (array $pick) => ['need' => $needs[$pick['need_id']], 'reasons' => $pick['reasons']])
            ->values();
    }

    /**
     * @return Collection<int, array{need: Need, reasons: list<string>, score: int}>
     */
    public function compute(User $user): Collection
    {
        $weights = config('idee.recommendations.weights');
        $followed = $this->follows->followedIds($user);
        $roles = $user->roles();
        $sectors = $user->sectors ?? [];
        $skills = collect($user->skills ?? [])->filter(fn (string $skill) => mb_strlen(trim($skill)) >= 3);

        $candidates = Need::query()
            ->where('status', NeedStatus::Ouvert)
            ->whereHas('project', fn (Builder $query) => $query->active()->where('owner_id', '!=', $user->id))
            ->whereDoesntHave('helpOffers', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(['project.sector', 'project.owner'])
            ->latest()
            ->limit(200)
            ->get();

        return $candidates
            ->map(function (Need $need) use ($weights, $followed, $roles, $sectors, $skills, $user) {
                $project = $need->project;
                $reasons = [];
                $score = 0;

                if ($need->type === NeedType::Financement && in_array(UserRole::Investisseur, $roles, true)) {
                    $score += $weights['role'];
                    $reasons[] = 'Vous êtes investisseur et ce projet cherche un financement.';
                } elseif (in_array($need->type, self::HELPER_NEEDS, true) && (in_array(UserRole::Contributeur, $roles, true) || in_array(UserRole::Expert, $roles, true))) {
                    $score += $weights['role'];
                    $reasons[] = 'Ce projet cherche : '.mb_strtolower($need->type->label()).', et vous proposez vos compétences.';
                }

                $text = mb_strtolower($need->title.' '.$need->description.' '.$need->profile_sought);
                $skill = $skills->first(fn (string $skill) => str_contains($text, mb_strtolower(trim($skill))));
                if ($skill !== null) {
                    $score += $weights['skill'];
                    $reasons[] = 'Il cherche « '.trim($skill).' », une de vos compétences.';
                }

                if ($project->sector && in_array($project->sector->slug, $sectors, true)) {
                    $score += $weights['sector'];
                    $reasons[] = "Secteur de votre profil : {$project->sector->name}.";
                } elseif ($project->sector && in_array($project->sector_id, $followed['sectors'], true)) {
                    $score += $weights['followed'];
                    $reasons[] = "Vous suivez le secteur {$project->sector->name}.";
                }

                if (in_array($project->id, $followed['projects'], true)) {
                    $score += $weights['followed'];
                    $reasons[] = 'Vous suivez ce projet.';
                } elseif (in_array($project->owner_id, $followed['users'], true)) {
                    $score += $weights['followed'];
                    $reasons[] = "Vous suivez {$project->owner->name}.";
                }

                if ($user->region && $project->region && mb_strtolower($user->region) === mb_strtolower($project->region)) {
                    $score += $weights['region'];
                    $reasons[] = "Le projet est dans votre région : {$project->region}.";
                }

                return ['need' => $need, 'reasons' => $reasons, 'score' => $score];
            })
            ->filter(fn (array $pick) => $pick['score'] > 0)
            ->sortByDesc('score')
            ->take(config('idee.recommendations.per_day'))
            ->values();
    }
}
