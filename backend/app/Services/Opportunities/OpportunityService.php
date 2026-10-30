<?php

namespace App\Services\Opportunities;

use App\Enums\ApplicationStatus;
use App\Enums\OpportunityKind;
use App\Models\Opportunity;
use App\Models\OpportunityApplication;
use App\Models\User;
use App\Notifications\ApplicationReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Répertoire des programmes, concours et financements (OP-01) et suivi des
 * candidatures déposées auprès des guichets extérieurs (parcours financement).
 */
class OpportunityService
{
    /**
     * Opportunités ouvertes, les dates limites les plus proches d'abord.
     *
     * @return Builder<Opportunity>
     */
    public function search(?OpportunityKind $kind = null, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        return Opportunity::query()
            ->open()
            ->when($kind, fn (Builder $query) => $query->where('kind', $kind))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('organisation', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")))
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->orderBy('title');
    }

    public function applicationOf(?User $user, Opportunity $opportunity): ?OpportunityApplication
    {
        return $user ? OpportunityApplication::whereBelongsTo($user)->whereBelongsTo($opportunity)->first() : null;
    }

    /**
     * @return Collection<int, OpportunityApplication>
     */
    public function applicationsOf(User $user): Collection
    {
        return OpportunityApplication::whereBelongsTo($user)->with('opportunity')->latest('updated_at')->get();
    }

    public function track(User $user, Opportunity $opportunity, ApplicationStatus $status, ?string $note = null, ?string $remindOn = null): OpportunityApplication
    {
        $application = $this->applicationOf($user, $opportunity) ?? new OpportunityApplication;
        $remindOn = $remindOn ? Carbon::parse($remindOn)->startOfDay() : null;

        if ($application->remind_on?->toDateString() !== $remindOn?->toDateString()) {
            $application->reminded_at = null;
        }

        $application->user()->associate($user);
        $application->opportunity()->associate($opportunity);
        $application->fill(['note' => $note, 'remind_on' => $remindOn]);
        $application->status = $status;
        $application->save();

        return $application;
    }

    public function forget(User $user, Opportunity $opportunity): void
    {
        $this->applicationOf($user, $opportunity)?->delete();
    }

    /**
     * Rappels du jour sur les candidatures encore en cours.
     */
    public function sendReminders(): int
    {
        $sent = 0;

        OpportunityApplication::query()
            ->whereNotNull('remind_on')
            ->whereDate('remind_on', '<=', today())
            ->whereNull('reminded_at')
            ->whereNotIn('status', [ApplicationStatus::Acceptee, ApplicationStatus::Refusee])
            ->whereHas('user')
            ->with(['user', 'opportunity'])
            ->each(function (OpportunityApplication $application) use (&$sent) {
                $application->user->notify(new ApplicationReminder($application));
                $application->forceFill(['reminded_at' => now()])->save();
                $sent++;
            });

        return $sent;
    }
}
