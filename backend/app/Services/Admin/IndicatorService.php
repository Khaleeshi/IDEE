<?php

namespace App\Services\Admin;

use App\Enums\CollaborationStatus;
use App\Enums\ContactRequestStatus;
use App\Enums\NeedStatus;
use App\Enums\NeedType;
use App\Enums\StepStatus;
use App\Enums\VerificationLevel;
use App\Models\Activity;
use App\Models\BusinessPlan;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\HelpOffer;
use App\Models\Message;
use App\Models\Need;
use App\Models\Project;
use App\Models\StepProgress;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tableau de bord des indicateurs (AD-03), selon les définitions de la
 * section 18 du cahier des charges. L'indicateur principal : la part des
 * projets qui obtiennent une réponse utile en 30 jours.
 */
class IndicatorService
{
    /**
     * @return list<array{label: string, definition: string, value: string, target: ?string}>
     */
    public function all(): array
    {
        $targets = config('idee.indicator_targets');
        $financed = Need::where('type', NeedType::Financement)->where('status', NeedStatus::Comble);

        return [
            $this->row('Projets avec réponse utile sous 30 jours', 'Part des projets publiés depuis plus de 30 jours ayant reçu une proposition d\'aide ou une demande acceptée dans les 30 jours', $this->percent($this->usefulResponseRate()), $targets['useful_response_rate'].' %'),
            $this->row('Taux de réponse aux demandes', 'Part des demandes de contact closes qui ont reçu une réponse plutôt qu\'une expiration', $this->percent($this->replyRate()), $targets['reply_rate'].' %'),
            $this->row('Délai avant la première mise en relation', 'Délai médian entre la publication d\'un projet et la première demande acceptée', $this->days($this->medianDaysToFirstMatch()), 'moins de '.$targets['days_to_first_match'].' jours'),
            $this->row('Projets actifs', 'Projets mis à jour depuis moins de '.config('idee.dormant_after_days').' jours', (string) Project::active()->where('last_activity_at', '>=', now()->subDays(config('idee.dormant_after_days')))->count(), (string) $targets['active_projects']),
            $this->row('Membres actifs', 'Membres ayant agi au moins une fois ces 30 derniers jours', (string) $this->activeMemberIds(now()->subDays(30))->count(), (string) $targets['active_members']),
            $this->row('Investisseurs et mentors actifs', 'Profils vérifiés ayant répondu à au moins une demande ces 30 derniers jours', (string) $this->activeVerifiedResponders(), (string) $targets['active_investors']),
            $this->row('Financements déclarés', 'Besoins de financement clôturés comme comblés par les porteurs', $financed->count().' · '.number_format((int) $financed->sum('amount_fcfa'), 0, ',', "\u{00A0}").' FCFA', null),
            $this->row('Démarches terminées', 'Étapes de parcours marquées terminées, en cumul', (string) StepProgress::where('status', StepStatus::Terminee)->count(), (string) $targets['finished_steps']),
            $this->row('Rétention à 30 jours', 'Part des inscrits depuis plus de 30 jours qui ont encore agi après leur trentième jour', $this->percent($this->retentionRate()), $targets['retention_rate'].' %'),
            $this->row('Collaborations confirmées', 'Collaborations déclarées par un membre et confirmées par l\'autre (CO-03), en cumul', (string) Collaboration::where('status', CollaborationStatus::Confirmee)->count(), null),
            $this->row('Business plans rédigés', 'Business plans produits par l\'assistant IA, en cumul', (string) BusinessPlan::count(), null),
            $this->row('Satisfaction', 'Enquête de satisfaction auprès des membres', 'à venir', null),
        ];
    }

    /**
     * @return array{label: string, definition: string, value: string, target: ?string}
     */
    private function row(string $label, string $definition, string $value, ?string $target): array
    {
        return compact('label', 'definition', 'value', 'target');
    }

    public function usefulResponseRate(): ?float
    {
        $projects = Project::withTrashed()->where('created_at', '<=', now()->subDays(30))->get(['id', 'created_at']);

        if ($projects->isEmpty()) {
            return null;
        }

        $firstOffers = HelpOffer::query()
            ->join('needs', 'needs.id', '=', 'help_offers.need_id')
            ->selectRaw('needs.project_id, min(help_offers.created_at) as first_at')
            ->groupBy('needs.project_id')
            ->pluck('first_at', 'project_id');
        $firstMatches = $this->firstMatches();

        $answered = $projects->filter(function (Project $project) use ($firstOffers, $firstMatches) {
            $limit = $project->created_at->copy()->addDays(30);
            $dates = collect([$firstOffers[$project->id] ?? null, $firstMatches[$project->id] ?? null])->filter();

            return $dates->contains(fn ($date) => Carbon::parse($date)->lte($limit));
        });

        return $answered->count() / $projects->count();
    }

    public function replyRate(): ?float
    {
        $answered = ContactRequest::whereIn('status', ContactRequestStatus::responses())->count();
        $expired = ContactRequest::where('status', ContactRequestStatus::Expiree)->count();

        return $answered + $expired > 0 ? $answered / ($answered + $expired) : null;
    }

    public function medianDaysToFirstMatch(): ?float
    {
        $created = Project::withTrashed()->pluck('created_at', 'id');
        $delays = $this->firstMatches()
            ->map(fn ($date, $projectId) => isset($created[$projectId]) ? Carbon::parse($created[$projectId])->diffInDays(Carbon::parse($date)) : null)
            ->filter(fn ($days) => $days !== null)
            ->sort()
            ->values();

        return $delays->isEmpty() ? null : (float) $delays->median();
    }

    /**
     * Membres ayant publié, répondu, écrit ou avancé dans un parcours depuis la date donnée.
     *
     * @return Collection<int, string>
     */
    public function activeMemberIds(Carbon $since): Collection
    {
        return collect()
            ->merge(Activity::where('created_at', '>=', $since)->pluck('actor_id'))
            ->merge(ContactRequest::where('created_at', '>=', $since)->pluck('sender_id'))
            ->merge(ContactRequest::where('responded_at', '>=', $since)->pluck('recipient_id'))
            ->merge(HelpOffer::where('created_at', '>=', $since)->pluck('user_id'))
            ->merge(Message::where('created_at', '>=', $since)->pluck('sender_id'))
            ->merge(StepProgress::where('updated_at', '>=', $since)->pluck('user_id'))
            ->unique()
            ->values();
    }

    public function activeVerifiedResponders(): int
    {
        return User::where('verification_level', '>=', VerificationLevel::Identity->value)
            ->whereHas('receivedContactRequests', fn ($query) => $query->where('responded_at', '>=', now()->subDays(30)))
            ->count();
    }

    public function retentionRate(): ?float
    {
        $users = User::where('created_at', '<=', now()->subDays(30))->where('created_at', '>=', now()->subDays(180))->get(['id', 'created_at']);

        if ($users->isEmpty()) {
            return null;
        }

        $lastActions = $this->lastActionByUser(now()->subDays(180));
        $retained = $users->filter(fn (User $user) => isset($lastActions[$user->id])
            && Carbon::parse($lastActions[$user->id])->gte($user->created_at->copy()->addDays(30)));

        return $retained->count() / $users->count();
    }

    /**
     * Date de la dernière action de chaque membre depuis la date donnée.
     *
     * @return array<string, string>
     */
    private function lastActionByUser(Carbon $since): array
    {
        $sources = [
            [Activity::class, 'actor_id', 'created_at'],
            [ContactRequest::class, 'sender_id', 'created_at'],
            [ContactRequest::class, 'recipient_id', 'responded_at'],
            [HelpOffer::class, 'user_id', 'created_at'],
            [Message::class, 'sender_id', 'created_at'],
            [StepProgress::class, 'user_id', 'updated_at'],
        ];
        $latest = [];

        foreach ($sources as [$model, $userColumn, $dateColumn]) {
            $rows = $model::query()
                ->where($dateColumn, '>=', $since)
                ->selectRaw("{$userColumn} as user_id, max({$dateColumn}) as last_at")
                ->groupBy($userColumn)
                ->pluck('last_at', 'user_id');

            foreach ($rows as $userId => $date) {
                if (! isset($latest[$userId]) || Carbon::parse($date)->gt(Carbon::parse($latest[$userId]))) {
                    $latest[$userId] = $date;
                }
            }
        }

        return $latest;
    }

    /**
     * Date de la première demande acceptée, par projet.
     *
     * @return Collection<string, string>
     */
    private function firstMatches(): Collection
    {
        return ContactRequest::query()
            ->where('status', ContactRequestStatus::Interesse)
            ->whereNotNull('project_id')
            ->selectRaw('project_id, min(responded_at) as first_at')
            ->groupBy('project_id')
            ->pluck('first_at', 'project_id');
    }

    private function percent(?float $rate): string
    {
        return $rate === null ? '—' : (int) round($rate * 100).' %';
    }

    private function days(?float $days): string
    {
        return $days === null ? '—' : (int) round($days).' jour'.($days >= 2 ? 's' : '');
    }
}
