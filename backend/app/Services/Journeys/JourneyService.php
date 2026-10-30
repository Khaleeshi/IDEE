<?php

namespace App\Services\Journeys;

use App\Enums\NeedType;
use App\Enums\StepStatus;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Models\Project;
use App\Models\StepProgress;
use App\Models\User;
use App\Notifications\StepReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JourneyService
{
    /**
     * Parcours proposés au diagnostic de situation (PG-01).
     *
     * @return Collection<int, Journey>
     */
    public function all(): Collection
    {
        return Journey::withCount('steps')->orderBy('position')->get();
    }

    /**
     * Parcours suivis par le membre, avec le nombre d'étapes terminées.
     *
     * @return Collection<int, Journey>
     */
    public function followedBy(User $user): Collection
    {
        return Journey::query()
            ->whereHas('steps.progress', fn (Builder $query) => $query->whereBelongsTo($user))
            ->withCount([
                'steps',
                'steps as done_steps_count' => fn (Builder $query) => $query->whereHas('progress', fn (Builder $query) => $query
                    ->whereBelongsTo($user)
                    ->where('status', StepStatus::Terminee)),
            ])
            ->orderBy('position')
            ->get();
    }

    /**
     * Avancement du membre sur chaque étape du parcours, indexé par étape.
     *
     * @return Collection<string, StepProgress>
     */
    public function progressFor(?User $user, Journey $journey): Collection
    {
        if ($user === null) {
            return new Collection;
        }

        return $user->stepProgress()
            ->whereIn('journey_step_id', $journey->steps()->select('id'))
            ->get()
            ->keyBy('journey_step_id');
    }

    /**
     * Charge les étapes d'un parcours avec l'avancement du membre (pour l'API).
     */
    public function withProgress(Journey $journey, ?User $user): Journey
    {
        $journey->load('steps');

        if ($user !== null) {
            $journey->load(['steps.progress' => fn ($query) => $query->whereBelongsTo($user)]);
        }

        return $journey;
    }

    /**
     * Commence un parcours : chaque étape entre dans la liste de tâches du membre (PG-05).
     */
    public function start(User $user, Journey $journey): void
    {
        $existing = $this->progressFor($user, $journey);

        foreach ($journey->steps as $step) {
            if ($existing->has($step->id)) {
                continue;
            }

            $progress = new StepProgress;
            $progress->user()->associate($user);
            $progress->step()->associate($step);
            $progress->status = StepStatus::AFaire;
            $progress->save();
        }
    }

    /**
     * Retire un parcours de la liste de tâches du membre.
     */
    public function leave(User $user, Journey $journey): void
    {
        $user->stepProgress()->whereIn('journey_step_id', $journey->steps()->select('id'))->delete();
    }

    /**
     * Met à jour le statut, la note et le rappel d'une étape.
     */
    public function updateStep(User $user, JourneyStep $step, StepStatus $status, ?string $remindOn = null, ?string $note = null): StepProgress
    {
        $this->start($user, $step->journey);

        $progress = $user->stepProgress()->where('journey_step_id', $step->id)->firstOrFail();
        $remindOn = $remindOn ? Carbon::parse($remindOn)->startOfDay() : null;

        // Un nouveau rappel repart de zéro, même si l'ancien a déjà été envoyé.
        if ($progress->remind_on?->toDateString() !== $remindOn?->toDateString()) {
            $progress->reminded_at = null;
        }

        $progress->fill(['note' => $note, 'remind_on' => $remindOn]);
        $progress->status = $status;
        $progress->completed_at = $status->isDone() ? ($progress->completed_at ?? now()) : null;
        $progress->save();

        return $progress;
    }

    /**
     * Rappels arrivés à échéance sur des étapes non terminées (PG-05).
     *
     * @return Collection<int, StepProgress>
     */
    public function dueReminders(User $user): Collection
    {
        return $this->due($user->stepProgress()->getQuery())
            ->with('step.journey')
            ->orderBy('remind_on')
            ->get();
    }

    public function dueRemindersCount(User $user): int
    {
        return $this->due($user->stepProgress()->getQuery())->count();
    }

    /**
     * Envoie les rappels du jour, une seule fois chacun.
     *
     * @return int Nombre de rappels envoyés.
     */
    public function sendReminders(): int
    {
        $sent = 0;

        $this->due(StepProgress::query())
            ->whereNull('reminded_at')
            ->whereHas('user')
            ->with(['user', 'step.journey'])
            ->each(function (StepProgress $progress) use (&$sent) {
                $progress->user->notify(new StepReminder($progress));
                $progress->forceFill(['reminded_at' => now()])->save();
                $sent++;
            });

        return $sent;
    }

    /**
     * Fiches dont la source n'a pas été revue à temps, à signaler à l'équipe (RG-10).
     *
     * @return Collection<int, JourneyStep>
     */
    public function staleSteps(): Collection
    {
        return JourneyStep::stale()->with('journey')->orderBy('journey_id')->orderBy('position')->get();
    }

    /**
     * Projet sur lequel le membre peut publier un appel à l'aide pour une étape.
     */
    public function helpTarget(User $user): ?Project
    {
        return $user->projects()->latest('last_activity_at')->first();
    }

    /**
     * Appel à action prérempli pour demander de l'aide sur une étape :
     * tous les parcours aboutissent à un projet qui exprime ses besoins.
     *
     * @return array{type: string, title: string, description: string}
     */
    public function needDraft(JourneyStep $step): array
    {
        return [
            'type' => NeedType::Competence->value,
            'title' => Str::limit('Aide pour : '.$step->title, 150, ''),
            'description' => "J'avance dans le parcours « {$step->journey->title} » et je cherche une personne qui connaît "
                ."l'étape « {$step->title} » pour me conseiller.",
        ];
    }

    /**
     * @param  Builder<StepProgress>  $query
     * @return Builder<StepProgress>
     */
    /**
     * Remplace ou retire l'enregistrement audio en wolof d'une fiche (PG-07).
     */
    public function setWolofAudio(JourneyStep $step, ?UploadedFile $audio, bool $remove = false): JourneyStep
    {
        if (($audio !== null || $remove) && $step->audio_path) {
            Storage::disk('public')->delete($step->audio_path);
            $step->audio_path = null;
        }

        if ($audio !== null) {
            $step->audio_path = $audio->store('fiches/audio', 'public');
        }

        $step->save();

        return $step;
    }

    private function due(Builder $query): Builder
    {
        return $query
            ->whereNotNull('remind_on')
            ->whereDate('remind_on', '<=', today())
            ->where('status', '!=', StepStatus::Terminee);
    }
}
