<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Project;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Assistant IA dans l'API : questions de clarification (IA-01) et business plan (IA-02).
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
