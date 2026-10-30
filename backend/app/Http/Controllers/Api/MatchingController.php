<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\ProposeMatchRequest;
use App\Http\Resources\EnumResource;
use App\Models\Project;
use App\Models\ProjectMatch;
use App\Services\Projects\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Rapprochement entre projets similaires (PR-08, RG-12) : fiches publiques
 * seulement, conversation ouverte après l'accord des deux porteurs.
 */
class MatchingController extends Controller
{
    public function __construct(private readonly MatchingService $matching) {}

    public function index(Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $proposals = $this->matching->proposalsFor($project);

        return response()->json(['data' => [
            'open_to_matching' => $project->open_to_matching,
            'suggestions' => $this->matching->suggestions($project)->map(fn (Project $candidate) => [
                ...$this->publicProject($candidate),
                'reasons' => $candidate->match_reasons,
            ]),
            'received' => $proposals['received']->map(fn (ProjectMatch $match) => [...$this->match($match), 'project' => $this->publicProject($match->project)]),
            'sent' => $proposals['sent']->map(fn (ProjectMatch $match) => [...$this->match($match), 'project' => $this->publicProject($match->targetProject)]),
        ]]);
    }

    public function toggle(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);
        $request->validate(['open' => ['required', 'boolean']]);

        return response()->json(['data' => ['open_to_matching' => $this->matching->setOpen($project, $request->boolean('open'))->open_to_matching]]);
    }

    public function store(ProposeMatchRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $match = $this->matching->propose($project, Project::findOrFail($request->validated('target_project_id')), $request->user(), $request->validated('message'));

        return response()->json(['data' => $this->match($match)], Response::HTTP_CREATED);
    }

    public function respond(DecisionRequest $request, ProjectMatch $projectMatch): JsonResponse
    {
        Gate::authorize('respond', $projectMatch);

        return response()->json(['data' => $this->match($this->matching->respond($projectMatch, $request->boolean('accepted')))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function match(ProjectMatch $match): array
    {
        return [
            'id' => $match->id,
            'status' => EnumResource::make($match->status),
            'message' => $match->message,
            'contact_request_id' => $match->contact_request_id,
            'created_at' => $match->created_at?->toIso8601String(),
        ];
    }

    /**
     * Seulement ce que la fiche publique montre déjà.
     *
     * @return array<string, mixed>
     */
    private function publicProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'summary' => $project->summary,
            'sector' => $project->sector?->name,
            'region' => $project->region,
            'stage' => EnumResource::make($project->stage),
        ];
    }
}
