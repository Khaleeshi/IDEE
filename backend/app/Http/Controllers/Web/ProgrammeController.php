<?php

namespace App\Http\Controllers\Web;

use App\Enums\CheckinStatus;
use App\Enums\ProgrammeApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\CohortProject;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\ProgrammeApplication;
use App\Models\Project;
use App\Models\User;
use App\Services\Organisations\ProgrammeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Candidatures depuis un dossier IDEE (OR-02) et suivi de cohortes (OR-03).
 */
class ProgrammeController extends Controller
{
    public function __construct(private readonly ProgrammeService $programmes) {}

    public function apply(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'string', 'exists:projects,id'],
            'answers' => ['array'],
            'answers.*' => ['nullable', 'string', 'max:3000'],
        ], ['project_id.required' => 'Choisissez le projet qui candidate.']);

        $application = $this->programmes->apply(
            $opportunity,
            Project::findOrFail($data['project_id']),
            $request->user(),
            $data['answers'] ?? [],
            $request->boolean('share_business_plan'),
        );

        return redirect()->route('applications.show', $application)->with('status', 'Candidature envoyée. Vous serez prévenu de la décision.');
    }

    public function applications(Opportunity $opportunity): View
    {
        $organisation = $opportunity->organisationPage;
        abort_if($organisation === null, 404);
        Gate::authorize('manageProgrammes', $organisation);

        return view('programmes.applications', [
            'programme' => $opportunity,
            'organisation' => $organisation,
            'applications' => $opportunity->programmeApplications()->with(['project', 'user'])->get(),
        ]);
    }

    public function show(Request $request, ProgrammeApplication $programmeApplication): View
    {
        $canReview = $this->programmes->canReview($request->user(), $programmeApplication);
        abort_unless($canReview || $programmeApplication->user_id === $request->user()->id, 404);

        $organisation = $programmeApplication->opportunity->organisationPage;

        return view('programmes.application', [
            'application' => $programmeApplication->load(['project', 'user', 'opportunity']),
            'canReview' => $canReview,
            'decisions' => ProgrammeApplicationStatus::decisions(),
            'cohorts' => $canReview ? $organisation->cohorts : collect(),
        ]);
    }

    public function decide(Request $request, ProgrammeApplication $programmeApplication): RedirectResponse
    {
        abort_unless($this->programmes->canReview($request->user(), $programmeApplication), 403);
        $data = $request->validate([
            'status' => ['required', Rule::enum(ProgrammeApplicationStatus::class)],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->programmes->decide($programmeApplication, ProgrammeApplicationStatus::from($data['status']), $data['feedback'] ?? null);

        return redirect()->route('applications.show', $programmeApplication)->with('status', 'Décision envoyée au porteur.');
    }

    public function cohorts(Organisation $organisation): View
    {
        Gate::authorize('manageProgrammes', $organisation);

        return view('programmes.cohorts', [
            'organisation' => $organisation,
            'cohorts' => $organisation->cohorts()->withCount('members')->get(),
            'programmes' => $organisation->opportunities()->pluck('title', 'id'),
        ]);
    }

    public function storeCohort(Request $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('manageProgrammes', $organisation);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'opportunity_id' => ['nullable', 'string', 'exists:opportunities,id'],
        ], ['name.required' => 'Donnez un nom à la cohorte, par exemple « Cohorte 2027 ».']);

        $cohort = $this->programmes->createCohort(
            $organisation,
            $data['name'],
            isset($data['starts_on']) ? Carbon::parse($data['starts_on']) : null,
            isset($data['ends_on']) ? Carbon::parse($data['ends_on']) : null,
            isset($data['opportunity_id']) ? Opportunity::find($data['opportunity_id']) : null,
        );

        return redirect()->route('cohorts.show', $cohort)->with('status', 'Cohorte créée.');
    }

    public function cohort(Request $request, Cohort $cohort): View
    {
        // IN-02 : les responsables voient toute la cohorte ; un coach, les projets qu'il suit.
        $manages = $cohort->organisation->managesProgrammes($request->user());
        $members = $cohort->members()->with(['project.openNeeds', 'latestCheckin', 'coach'])
            ->when(! $manages, fn ($query) => $query->where('coach_id', $request->user()->id))
            ->get();
        abort_unless($manages || $members->isNotEmpty(), 403);

        return view('programmes.cohort', [
            'cohort' => $cohort,
            'manages' => $manages,
            'coaches' => $manages ? $cohort->organisation->coachOptions() : collect(),
            'members' => $members,
            'retained' => ProgrammeApplication::query()
                ->where('status', ProgrammeApplicationStatus::Retenue)
                ->whereHas('opportunity', fn ($query) => $query->where('organisation_id', $cohort->organisation_id))
                ->whereNotIn('project_id', $cohort->members()->pluck('project_id'))
                ->with('project')
                ->get(),
            'statuses' => CheckinStatus::cases(),
        ]);
    }

    public function addToCohort(Request $request, Cohort $cohort): RedirectResponse
    {
        Gate::authorize('manageProgrammes', $cohort->organisation);
        $data = $request->validate(['application_id' => ['required', 'string', 'exists:programme_applications,id']]);

        $this->programmes->addToCohort($cohort, ProgrammeApplication::findOrFail($data['application_id']));

        return redirect()->route('cohorts.show', $cohort)->with('status', 'Projet ajouté à la cohorte.');
    }

    public function checkin(Request $request, CohortProject $cohortProject): RedirectResponse
    {
        abort_unless($cohortProject->coach_id === $request->user()->id || $cohortProject->cohort->organisation->managesProgrammes($request->user()), 403);
        $data = $request->validate([
            'status' => ['required', Rule::enum(CheckinStatus::class)],
            'note' => ['required', 'string', 'max:2000'],
        ], ['note.required' => 'Notez en une phrase où en est le projet.']);

        $this->programmes->checkin($cohortProject, $request->user(), CheckinStatus::from($data['status']), $data['note']);

        return redirect()->to(route('cohorts.show', $cohortProject->cohort).'#projet-'.$cohortProject->id)->with('status', 'Point de suivi enregistré.');
    }

    /**
     * IN-02 : confier un projet de la cohorte à un coach ou mentor de la structure.
     */
    public function assignCoach(Request $request, CohortProject $cohortProject): RedirectResponse
    {
        $organisation = $cohortProject->cohort->organisation;
        Gate::authorize('manageProgrammes', $organisation);
        $data = $request->validate(['coach_id' => ['nullable', 'string', Rule::in($organisation->coachOptions()->keys()->all())]],
            ['coach_id.in' => 'Choisissez un coach ou un responsable de votre structure.']);

        $this->programmes->assignCoach($cohortProject, isset($data['coach_id']) ? User::find($data['coach_id']) : null);

        return redirect()->to(route('cohorts.show', $cohortProject->cohort).'#projet-'.$cohortProject->id)->with('status', 'Suivi du projet mis à jour.');
    }

    /**
     * IN-03 : tableau de suivi de la cohorte, à ouvrir dans un tableur.
     */
    public function export(Cohort $cohort): StreamedResponse
    {
        Gate::authorize('manageProgrammes', $cohort->organisation);

        return response()->streamDownload(function () use ($cohort) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // accents lisibles dans Excel
            fputcsv($out, ['Projet', 'Porteur', 'Région', 'Stade', 'Score de préparation', 'Besoins ouverts', 'Coach', 'Dernier point', 'Date du dernier point', 'Note'], ';');
            $cohort->members()->with(['project.owner', 'project.openNeeds', 'coach', 'latestCheckin'])->get()->each(function (CohortProject $member) use ($out) {
                $project = $member->project;
                fputcsv($out, [
                    $project->title,
                    $project->owner?->name,
                    $project->region,
                    $project->stage?->label(),
                    $project->readiness_score,
                    $project->openNeeds->count(),
                    $member->coach?->name,
                    $member->latestCheckin?->status->label(),
                    $member->latestCheckin?->created_at->format('d/m/Y'),
                    $member->latestCheckin?->note,
                ], ';');
            });
            fclose($out);
        }, 'cohorte-'.Str::slug($cohort->name).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
