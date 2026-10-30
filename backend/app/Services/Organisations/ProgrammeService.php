<?php

namespace App\Services\Organisations;

use App\Enums\AssistantStatus;
use App\Enums\CheckinStatus;
use App\Enums\OrganisationRole;
use App\Enums\ProgrammeApplicationStatus;
use App\Models\BusinessPlan;
use App\Models\Cohort;
use App\Models\CohortCheckin;
use App\Models\CohortProject;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\OrganisationMember;
use App\Models\ProgrammeApplication;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProgrammeNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Candidatures structurées envoyées depuis un dossier IDEE (OR-02) et suivi
 * des cohortes d'incubateurs et de programmes (OR-03).
 */
class ProgrammeService
{
    /**
     * Questions posées à tous les candidats, avant celles du programme.
     */
    public const COMMON_QUESTIONS = [
        'avancement' => 'Où en est votre projet ? Donnez quelques chiffres si possible.',
        'besoins' => 'De quoi votre projet a-t-il le plus besoin aujourd\'hui ?',
        'motivation' => 'Qu\'attendez-vous de ce programme ?',
    ];

    /**
     * @return array<string, string>
     */
    public function questions(Opportunity $programme): array
    {
        $custom = collect($programme->application_questions ?? [])
            ->take(config('idee.programmes.max_questions'))
            ->mapWithKeys(fn (string $question, int $index) => ['q'.($index + 1) => $question]);

        return [...self::COMMON_QUESTIONS, ...$custom->all()];
    }

    /**
     * Envoie une candidature ; le business plan n'est joint qu'avec l'accord du porteur.
     *
     * @param  array<string, string|null>  $answers
     *
     * @throws ValidationException
     */
    public function apply(Opportunity $programme, Project $project, User $applicant, array $answers, bool $shareBusinessPlan): ProgrammeApplication
    {
        if (! $programme->takesApplications()) {
            throw ValidationException::withMessages(['project_id' => 'Ce programme ne reçoit pas de candidatures sur IDEE.']);
        }

        if (! $project->isOwnedBy($applicant)) {
            throw ValidationException::withMessages(['project_id' => 'Vous candidatez avec l\'un de vos propres projets.']);
        }

        if ($programme->programmeApplications()->where('project_id', $project->id)->exists()) {
            throw ValidationException::withMessages(['project_id' => 'Ce projet a déjà candidaté à ce programme.']);
        }

        $written = [];
        foreach ($this->questions($programme) as $key => $question) {
            $answer = trim((string) ($answers[$key] ?? ''));

            if (mb_strlen($answer) < 10) {
                throw ValidationException::withMessages(["answers.{$key}" => 'Répondez à chaque question en une ou deux phrases.']);
            }

            $written[] = ['question' => $question, 'answer' => $answer];
        }

        $plan = $shareBusinessPlan
            ? BusinessPlan::where('project_id', $project->id)->where('status', AssistantStatus::Pret)->orderByDesc('version')->first()
            : null;

        $application = new ProgrammeApplication;
        $application->opportunity()->associate($programme);
        $application->project()->associate($project);
        $application->user()->associate($applicant);
        $application->forceFill([
            'answers' => $written,
            'business_plan' => $plan?->sections,
            'status' => ProgrammeApplicationStatus::Envoyee,
        ])->save();

        $this->managers($programme->organisationPage)->each(fn (User $manager) => $manager->notify(new ProgrammeNotice(
            $application,
            'Nouvelle candidature de « '.$project->title.' » au programme « '.$programme->title.' ».',
        )));

        return $application;
    }

    /**
     * @throws ValidationException
     */
    public function decide(ProgrammeApplication $application, ProgrammeApplicationStatus $status, ?string $feedback = null): ProgrammeApplication
    {
        if (! in_array($status, ProgrammeApplicationStatus::decisions(), true)) {
            throw ValidationException::withMessages(['status' => 'Choisissez une décision.']);
        }

        $application->forceFill([
            'status' => $status,
            'feedback' => filled($feedback) ? trim($feedback) : null,
            'decided_at' => now(),
        ])->save();

        $application->user->notify(new ProgrammeNotice(
            $application,
            'Votre candidature au programme « '.$application->opportunity->title.' » : '.mb_strtolower($status->label()).'.',
        ));

        return $application;
    }

    public function createCohort(Organisation $organisation, string $name, ?Carbon $startsOn = null, ?Carbon $endsOn = null, ?Opportunity $programme = null): Cohort
    {
        $cohort = new Cohort(['name' => trim($name), 'starts_on' => $startsOn, 'ends_on' => $endsOn]);
        $cohort->organisation()->associate($organisation);
        $cohort->opportunity()->associate($programme?->organisation_id === $organisation->id ? $programme : null);
        $cohort->save();

        return $cohort;
    }

    /**
     * Seul un projet retenu à l'un des programmes de l'organisation entre dans une cohorte :
     * sa candidature vaut accord du porteur.
     *
     * @throws ValidationException
     */
    public function addToCohort(Cohort $cohort, ProgrammeApplication $application): CohortProject
    {
        if ($application->status !== ProgrammeApplicationStatus::Retenue || $application->opportunity->organisation_id !== $cohort->organisation_id) {
            throw ValidationException::withMessages(['application' => 'Seuls les projets retenus à vos programmes entrent dans une cohorte.']);
        }

        $member = $cohort->members()->firstOrNew(['project_id' => $application->project_id]);
        $member->project()->associate($application->project_id);
        $member->save();

        return $member;
    }

    public function checkin(CohortProject $member, User $author, CheckinStatus $status, string $note): CohortCheckin
    {
        $checkin = new CohortCheckin(['note' => trim($note)]);
        $checkin->forceFill(['cohort_project_id' => $member->id, 'status' => $status]);
        $checkin->author()->associate($author);
        $checkin->save();

        return $checkin;
    }

    public function canReview(?User $user, ProgrammeApplication $application): bool
    {
        return $application->opportunity->organisationPage?->isManagedBy($user) ?? false;
    }

    /**
     * @return Collection<int, User>
     */
    private function managers(?Organisation $organisation)
    {
        return $organisation?->members()->with('user')->where('role', OrganisationRole::Administrateur)->get()
            ->map(fn (OrganisationMember $member) => $member->user) ?? collect();
    }
}
