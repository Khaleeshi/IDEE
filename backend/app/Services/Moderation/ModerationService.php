<?php

namespace App\Services\Moderation;

use App\Enums\ModerationDecision;
use App\Enums\OrganisationRole;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Message;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ModerationNotice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Signalements et modération (CF-04, AD-01, section 8) : signalement en deux
 * clics, file triée par gravité, décision motivée, un recours examiné par
 * un second modérateur.
 */
class ModerationService
{
    /**
     * @throws ValidationException
     */
    public function report(User $reporter, User|Project|Message|Organisation $target, ReportReason $reason, ?string $details = null): Report
    {
        $targetUser = match (true) {
            $target instanceof User => $target,
            $target instanceof Project => $target->owner,
            $target instanceof Message => $target->sender,
            $target instanceof Organisation => $target->members()->where('role', OrganisationRole::Administrateur)->first()?->user,
        };

        if ($targetUser?->is($reporter)) {
            throw ValidationException::withMessages(['report' => 'Vous ne pouvez pas signaler votre propre contenu.']);
        }

        if ($target instanceof Message && ! $target->contactRequest->involves($reporter)) {
            throw ValidationException::withMessages(['report' => 'Vous ne pouvez signaler que les messages de vos conversations.']);
        }

        $alreadyOpen = Report::whereBelongsTo($reporter, 'reporter')
            ->whereMorphedTo('reportable', $target)
            ->whereIn('status', [ReportStatus::Ouvert, ReportStatus::EnRecours])
            ->exists();

        if ($alreadyOpen) {
            throw ValidationException::withMessages(['report' => 'Vous avez déjà signalé ce contenu ; l\'équipe l\'examine.']);
        }

        $report = new Report;
        $report->reporter()->associate($reporter);
        $report->reportable()->associate($target);
        $report->targetUser()->associate($targetUser);
        $report->forceFill([
            'reason' => $reason,
            'details' => $details,
            'priority' => $reason->priority(),
            'status' => ReportStatus::Ouvert,
        ])->save();

        return $report;
    }

    /**
     * File de modération : soupçons d'arnaque d'abord, puis les plus anciens.
     *
     * @return Collection<int, Report>
     */
    public function queue(ReportStatus $status = ReportStatus::Ouvert): Collection
    {
        return Report::where('status', $status)
            ->with(['reporter', 'targetUser', 'reportable'])
            ->orderByDesc('priority')
            ->oldest()
            ->get();
    }

    /**
     * @throws ValidationException
     */
    public function decide(Report $report, User $moderator, ModerationDecision $decision, ?string $note = null): Report
    {
        if ($report->status !== ReportStatus::Ouvert) {
            throw ValidationException::withMessages(['decision' => 'Ce signalement a déjà été traité.']);
        }

        DB::transaction(function () use ($report, $moderator, $decision, $note) {
            $report->moderator()->associate($moderator);
            $report->forceFill([
                'decision' => $decision,
                'decision_note' => $note,
                'decided_at' => now(),
                'status' => $decision->informsTarget() ? ReportStatus::Traite : ReportStatus::Clos,
            ]);

            $this->apply($report, $decision);
            $report->save();
        });

        if ($decision->informsTarget()) {
            $report->targetUser?->notify(new ModerationNotice($report));
        }

        return $report;
    }

    /**
     * La personne visée conteste la décision, une seule fois.
     *
     * @throws ValidationException
     */
    public function appeal(Report $report, User $user, string $text): Report
    {
        if ($report->target_user_id !== $user->id || ! $report->canBeAppealed()) {
            throw ValidationException::withMessages(['appeal' => 'Cette décision ne peut plus être contestée.']);
        }

        $report->forceFill(['appeal_text' => $text, 'appealed_at' => now(), 'status' => ReportStatus::EnRecours])->save();

        return $report;
    }

    /**
     * Un second modérateur maintient ou annule la décision contestée.
     *
     * @throws ValidationException
     */
    public function reviewAppeal(Report $report, User $moderator, bool $upheld): Report
    {
        if ($report->status !== ReportStatus::EnRecours) {
            throw ValidationException::withMessages(['appeal' => 'Aucun recours en attente sur ce signalement.']);
        }

        if ($report->moderator_id === $moderator->id) {
            throw ValidationException::withMessages(['appeal' => 'Un second modérateur doit examiner le recours.']);
        }

        DB::transaction(function () use ($report, $moderator, $upheld) {
            if (! $upheld) {
                $this->revert($report);
            }

            $report->forceFill([
                'appeal_moderator_id' => $moderator->id,
                'appeal_upheld' => $upheld,
                'appeal_decided_at' => now(),
                'status' => ReportStatus::Clos,
            ])->save();
        });

        $report->targetUser?->notify(new ModerationNotice($report));

        return $report;
    }

    /**
     * Décisions qui concernent un membre, pour qu'il en connaisse le motif.
     *
     * @return Collection<int, Report>
     */
    public function decisionsAbout(User $user): Collection
    {
        return Report::where('target_user_id', $user->id)
            ->whereNotNull('decision')
            ->where('decision', '!=', ModerationDecision::AucuneAction)
            ->latest('decided_at')
            ->get();
    }

    private function apply(Report $report, ModerationDecision $decision): void
    {
        $target = $report->reportable;
        $user = $report->targetUser;

        match ($decision) {
            ModerationDecision::Retrait => $this->remove($report, $target),
            ModerationDecision::Suspension => $user?->forceFill(['suspended_until' => now()->addDays(config('idee.moderation.suspension_days'))])->save(),
            ModerationDecision::Exclusion => $this->ban($user),
            default => null,
        };

        if (in_array($decision, [ModerationDecision::Suspension, ModerationDecision::Exclusion], true)) {
            $user?->tokens()->delete();
        }
    }

    private function remove(Report $report, mixed $target): void
    {
        match (true) {
            $target instanceof Project => $target->delete(),
            $target instanceof Message => $target->forceFill(['removed_at' => now()])->save(),
            $target instanceof User => $this->hideProfile($report, $target),
            $target instanceof Organisation => $this->hideOrganisation($report, $target),
            default => null,
        };
    }

    private function hideOrganisation(Report $report, Organisation $organisation): void
    {
        $report->removed_content = $organisation->description;
        $organisation->forceFill(['description' => 'Présentation retirée par la modération.'])->save();
    }

    private function hideProfile(Report $report, User $user): void
    {
        $report->removed_content = $user->bio;
        $user->forceFill(['bio' => null])->save();
    }

    private function ban(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $user->forceFill(['banned_at' => now()])->save();
        $user->projects()->each(fn (Project $project) => $project->delete());
    }

    /**
     * Rétablit ce que la décision annulée avait retiré.
     */
    private function revert(Report $report): void
    {
        $target = $report->reportable;
        $user = $report->targetUser;

        match ($report->decision) {
            ModerationDecision::Retrait => match (true) {
                $target instanceof Project => $target->restore(),
                $target instanceof Message => $target->forceFill(['removed_at' => null])->save(),
                $target instanceof User => $target->forceFill(['bio' => $report->removed_content])->save(),
                $target instanceof Organisation => $target->forceFill(['description' => $report->removed_content])->save(),
                default => null,
            },
            ModerationDecision::Suspension => $user?->forceFill(['suspended_until' => null])->save(),
            ModerationDecision::Exclusion => $this->unban($user, $report),
            default => null,
        };
    }

    private function unban(?User $user, Report $report): void
    {
        if ($user === null) {
            return;
        }

        $user->forceFill(['banned_at' => null])->save();
        Project::onlyTrashed()
            ->where('owner_id', $user->id)
            ->where('deleted_at', '>=', $report->decided_at->copy()->subMinute())
            ->restore();
    }
}
