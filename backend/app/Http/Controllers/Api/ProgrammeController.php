<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProgrammeApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnumResource;
use App\Models\Cohort;
use App\Models\CohortProject;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\ProgrammeApplication;
use App\Models\Project;
use App\Services\Organisations\ProgrammeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Candidatures depuis un dossier IDEE (OR-02) et suivi de cohortes (OR-03).
 */
class ProgrammeController extends Controller
{
    public function __construct(private readonly ProgrammeService $programmes) {}

    /**
     * Questions posées par le programme : `answers.{clé}` dans la candidature.
     */
    public function questions(Opportunity $opportunity): JsonResponse
    {
        return response()->json(['data' => [
            'takes_applications' => $opportunity->takesApplications(),
            'questions' => $this->programmes->questions($opportunity),
        ]]);
    }

    public function apply(Request $request, Opportunity $opportunity): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'string', 'exists:projects,id'],
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:3000'],
            'share_business_plan' => ['boolean'],
        ]);

        $application = $this->programmes->apply($opportunity, Project::findOrFail($data['project_id']), $request->user(), $data['answers'], $request->boolean('share_business_plan'));

        return response()->json(['data' => $this->present($application)], Response::HTTP_CREATED);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json(['data' => ProgrammeApplication::whereBelongsTo($request->user())->with('opportunity')->latest()->get()->map($this->present(...))]);
    }

    public function received(Opportunity $opportunity): JsonResponse
    {
        abort_if($opportunity->organisationPage === null, Response::HTTP_NOT_FOUND);
        Gate::authorize('update', $opportunity->organisationPage);

        return response()->json(['data' => $opportunity->programmeApplications()->with(['project', 'user'])->get()->map(fn (ProgrammeApplication $application) => [
            ...$this->present($application),
            'project' => ['id' => $application->project->id, 'title' => $application->project->title, 'readiness_score' => $application->project->readiness_score],
            'applicant' => ['id' => $application->user_id, 'name' => $application->user->name],
            'answers' => $application->answers,
            'business_plan' => $application->business_plan,
        ])]);
    }

    public function decide(Request $request, ProgrammeApplication $programmeApplication): JsonResponse
    {
        abort_unless($this->programmes->canReview($request->user(), $programmeApplication), Response::HTTP_FORBIDDEN);
        $data = $request->validate([
            'status' => ['required', Rule::enum(ProgrammeApplicationStatus::class)],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json(['data' => $this->present($this->programmes->decide($programmeApplication, ProgrammeApplicationStatus::from($data['status']), $data['feedback'] ?? null))]);
    }

    public function cohorts(Organisation $organisation): JsonResponse
    {
        Gate::authorize('update', $organisation);

        return response()->json(['data' => $organisation->cohorts()->withCount('members')->get()->map(fn (Cohort $cohort) => [
            'id' => $cohort->id,
            'name' => $cohort->name,
            'starts_on' => $cohort->starts_on?->toDateString(),
            'ends_on' => $cohort->ends_on?->toDateString(),
            'projects' => $cohort->members_count,
        ])]);
    }

    /**
     * Tableau de suivi d'une cohorte.
     */
    public function cohort(Cohort $cohort): JsonResponse
    {
        Gate::authorize('update', $cohort->organisation);

        return response()->json(['data' => $cohort->members()->with(['project.openNeeds', 'latestCheckin'])->get()->map(fn (CohortProject $member) => [
            'id' => $member->id,
            'project' => [
                'id' => $member->project->id,
                'title' => $member->project->title,
                'readiness_score' => $member->project->readiness_score,
                'open_needs' => $member->project->openNeeds->count(),
                'last_activity_at' => $member->project->last_activity_at?->toIso8601String(),
            ],
            'latest_checkin' => $member->latestCheckin ? [
                'status' => EnumResource::make($member->latestCheckin->status),
                'note' => $member->latestCheckin->note,
                'created_at' => $member->latestCheckin->created_at?->toIso8601String(),
            ] : null,
        ])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ProgrammeApplication $application): array
    {
        return [
            'id' => $application->id,
            'opportunity_id' => $application->opportunity_id,
            'project_id' => $application->project_id,
            'status' => EnumResource::make($application->status),
            'feedback' => $application->feedback,
            'business_plan_attached' => $application->business_plan !== null,
            'created_at' => $application->created_at?->toIso8601String(),
        ];
    }
}
