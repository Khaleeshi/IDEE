<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\OpportunityKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrackApplicationRequest;
use App\Http\Resources\EnumResource;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityApplication;
use App\Services\Opportunities\OpportunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OpportunityController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunities) {}

    /**
     * Répertoire des opportunités ouvertes (`type`, `q`).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OpportunityResource::collection(
            $this->opportunities->search(OpportunityKind::tryFrom((string) $request->query('type')), $request->query('q'))->paginate(30)
        );
    }

    public function show(Opportunity $opportunity): OpportunityResource
    {
        abort_unless($opportunity->is_published, Response::HTTP_NOT_FOUND);

        return new OpportunityResource($opportunity);
    }

    public function applications(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->opportunities->applicationsOf($request->user())->map(fn (OpportunityApplication $application) => [
            ...$this->application($application),
            'opportunity' => new OpportunityResource($application->opportunity),
        ])]);
    }

    public function track(TrackApplicationRequest $request, Opportunity $opportunity): JsonResponse
    {
        $application = $this->opportunities->track(
            $request->user(),
            $opportunity,
            ApplicationStatus::from($request->validated('status')),
            $request->validated('note'),
            $request->validated('remind_on'),
        );

        return response()->json(['data' => $this->application($application)]);
    }

    public function forget(Request $request, Opportunity $opportunity): Response
    {
        $this->opportunities->forget($request->user(), $opportunity);

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function application(OpportunityApplication $application): array
    {
        return [
            'opportunity_id' => $application->opportunity_id,
            'status' => EnumResource::make($application->status),
            'note' => $application->note,
            'remind_on' => $application->remind_on?->toDateString(),
            'updated_at' => $application->updated_at?->toIso8601String(),
        ];
    }
}
