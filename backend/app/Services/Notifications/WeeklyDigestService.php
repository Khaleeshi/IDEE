<?php

namespace App\Services\Notifications;

use App\Enums\ApplicationStatus;
use App\Enums\ContactRequestStatus;
use App\Models\Opportunity;
use App\Models\OpportunityApplication;
use App\Models\Project;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\WeeklyDigest;
use App\Services\Alerts\SavedSearchService;
use App\Services\Messaging\MessageService;
use App\Services\Opportunities\EligibilityService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Résumé hebdomadaire par e-mail (NO-04) : ce qui attend une réponse, les
 * nouveaux projets des alertes et les dates limites qui approchent.
 */
class WeeklyDigestService
{
    public function __construct(
        private readonly MessageService $messages,
        private readonly SavedSearchService $searches,
        private readonly EligibilityService $eligibility,
    ) {}

    /**
     * @return array{
     *     pending_requests: int,
     *     unread_messages: int,
     *     unread_notifications: int,
     *     alerts: list<array{label: string, count: int, url: string}>,
     *     deadlines: list<array{title: string, deadline: string, url: string}>,
     *     opportunities: list<array{title: string, project: string, deadline: string, url: string}>
     * }
     */
    public function summaryFor(User $user): array
    {
        $window = today()->addDays(config('idee.weekly_digest.deadline_window_days'));
        $tracked = OpportunityApplication::whereBelongsTo($user)->pluck('opportunity_id');

        $deadlines = OpportunityApplication::query()
            ->whereBelongsTo($user)
            ->where('status', ApplicationStatus::EnPreparation)
            ->whereHas('opportunity', fn (Builder $query) => $query->open()->whereNotNull('deadline')->whereDate('deadline', '<=', $window))
            ->with('opportunity')
            ->get()
            ->map(fn (OpportunityApplication $application) => $this->opportunityLine($application->opportunity))
            ->all();

        $closingSoon = Opportunity::query()->open()
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<=', $window)
            ->whereNotIn('id', $tracked)
            ->orderBy('deadline')
            ->get();

        $opportunities = $user->projects()->active()->get()
            ->flatMap(fn (Project $project) => $this->eligibility->eligibleFor($project, $closingSoon->toBase())
                ->map(fn (Opportunity $opportunity) => [...$this->opportunityLine($opportunity), 'project' => $project->title]))
            ->unique('url')
            ->take(3)
            ->values()
            ->all();

        $alerts = $user->savedSearches()->get()
            ->map(fn (SavedSearch $search) => [
                'label' => $search->label,
                'count' => $this->searches->newMatches($search, now()->subWeek())->count(),
                'url' => $search->url(),
            ])
            ->filter(fn (array $alert) => $alert['count'] > 0)
            ->values()
            ->all();

        return [
            'pending_requests' => $user->receivedContactRequests()
                ->whereIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])
                ->count(),
            'unread_messages' => $this->messages->unreadCount($user),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'alerts' => $alerts,
            'deadlines' => $deadlines,
            'opportunities' => $opportunities,
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function isWorthSending(array $summary): bool
    {
        return $summary['pending_requests'] > 0
            || $summary['unread_messages'] > 0
            || $summary['alerts'] !== []
            || $summary['deadlines'] !== []
            || $summary['opportunities'] !== [];
    }

    /**
     * Envoie le résumé aux membres à l'adresse confirmée qui ne l'ont pas désactivé.
     */
    public function send(): int
    {
        if (! config('idee.weekly_digest.enabled')) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->whereNull('banned_at')
            ->each(function (User $user) use (&$sent) {
                if (($user->contact_preferences['weekly_digest'] ?? true) === false || $user->isRestricted()) {
                    return;
                }

                $summary = $this->summaryFor($user);

                if ($this->isWorthSending($summary)) {
                    $user->notify(new WeeklyDigest($summary));
                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * @return array{title: string, deadline: string, url: string}
     */
    private function opportunityLine(Opportunity $opportunity): array
    {
        return [
            'title' => $opportunity->title,
            'deadline' => $opportunity->deadline->translatedFormat('d F'),
            'url' => route('opportunities.show', $opportunity),
        ];
    }
}
