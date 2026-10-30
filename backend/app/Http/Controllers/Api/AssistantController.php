<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssistantDocumentKind;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnumResource;
use App\Models\AssistantDocument;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Meeting;
use App\Models\Project;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Assistant IA dans l'API : questions de clarification (IA-01), business plan (IA-02),
 * préparation des rendez-vous (IA-05) et présentation du projet (IA-06).
 */
class AssistantController extends Controller
{
    public function __construct(private readonly AssistantService $assistant) {}

    public function clarification(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        return response()->json(['data' => $this->clarificationData(IdeaClarification::where('project_id', $project->id)->latest()->first())]);
    }

    public function clarify(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        return response()->json(['data' => $this->clarificationData($this->assistant->requestClarification($project)->fresh())], Response::HTTP_ACCEPTED);
    }

    public function answer(Request $request, IdeaClarification $ideaClarification): JsonResponse
    {
        Gate::authorize('update', $ideaClarification->project);
        $answers = $request->validate(['answers' => ['required', 'array'], 'answers.*' => ['nullable', 'string', 'max:2000']])['answers'];

        return response()->json(['data' => $this->clarificationData($this->assistant->answer($ideaClarification, $answers))]);
    }

    public function businessPlan(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        return response()->json(['data' => $this->planData(BusinessPlan::where('project_id', $project->id)->orderByDesc('version')->first())]);
    }

    public function generate(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        return response()->json(['data' => $this->planData($this->assistant->requestBusinessPlan($project)->fresh())], Response::HTTP_ACCEPTED);
    }

    public function update(Request $request, BusinessPlan $businessPlan): JsonResponse
    {
        Gate::authorize('update', $businessPlan->project);
        $sections = $request->validate(['sections' => ['required', 'array'], 'sections.*' => ['nullable', 'string', 'max:6000']])['sections'];

        return response()->json(['data' => $this->planData($this->assistant->editBusinessPlan($businessPlan, $sections))]);
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * IA-05 : préparation d'un rendez-vous pour la personne qui la demande.
     */
    public function prepareMeeting(Request $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('cancel', $meeting);

        return response()->json(['data' => $this->documentData($this->assistant->requestMeetingPreparation($meeting, $request->user())->fresh())], Response::HTTP_ACCEPTED);
    }

    public function document(Request $request, AssistantDocument $assistantDocument): JsonResponse
    {
        abort_unless($assistantDocument->user_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        return response()->json(['data' => $this->documentData($assistantDocument)]);
    }

    /**
     * IA-06 : dernière version de la présentation du projet.
     */
    public function pitchDeck(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $deck = AssistantDocument::where('kind', AssistantDocumentKind::Presentation)->where('project_id', $project->id)->orderByDesc('version')->first();

        return response()->json(['data' => $deck ? $this->documentData($deck) : null]);
    }

    public function generatePitchDeck(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        return response()->json(['data' => $this->documentData($this->assistant->requestPitchDeck($project, $request->user())->fresh())], Response::HTTP_ACCEPTED);
    }

    /**
     * Corrections du porteur : `slides.{clé}.title`, `bullets` (un point par ligne) et `notes`.
     */
    public function updatePitchDeck(Request $request, AssistantDocument $assistantDocument): JsonResponse
    {
        abort_unless($assistantDocument->kind === AssistantDocumentKind::Presentation, Response::HTTP_NOT_FOUND);
        Gate::authorize('update', $assistantDocument->project);
        $slides = $request->validate([
            'slides' => ['required', 'array'],
            'slides.*.title' => ['nullable', 'string', 'max:200'],
            'slides.*.bullets' => ['nullable', 'string', 'max:2000'],
            'slides.*.notes' => ['nullable', 'string', 'max:3000'],
        ])['slides'];

        return response()->json(['data' => $this->documentData($this->assistant->editPitchDeck($assistantDocument, $slides))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function documentData(AssistantDocument $document): array
    {
        return [
            'id' => $document->id,
            'kind' => EnumResource::make($document->kind),
            'status' => EnumResource::make($document->status),
            'project_id' => $document->project_id,
            'meeting_id' => $document->meeting_id,
            'version' => $document->version,
            'content' => $document->content,
            'error' => $document->error,
            'edited_at' => $document->edited_at?->toIso8601String(),
            'created_at' => $document->created_at?->toIso8601String(),
        ];
    }

    private function clarificationData(?IdeaClarification $clarification): ?array
    {
        return $clarification ? [
            'id' => $clarification->id,
            'status' => $clarification->status->value,
            'questions' => $clarification->questions ?? [],
            'suggestions' => $clarification->suggestions ?? [],
            'answers' => $clarification->answers ?? [],
            'error' => $clarification->error,
        ] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function planData(?BusinessPlan $plan): ?array
    {
        return $plan ? [
            'id' => $plan->id,
            'version' => $plan->version,
            'status' => $plan->status->value,
            'sections' => $plan->sections ?? [],
            'assumptions' => $plan->assumptions ?? [],
            'error' => $plan->error,
            'generated_at' => $plan->generated_at?->toIso8601String(),
            'edited_at' => $plan->edited_at?->toIso8601String(),
        ] : null;
    }
}
