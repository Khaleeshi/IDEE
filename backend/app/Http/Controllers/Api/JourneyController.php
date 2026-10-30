<?php

namespace App\Http\Controllers\Api;

use App\Enums\StepStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LegalFormRequest;
use App\Http\Requests\UpdateStepProgressRequest;
use App\Http\Resources\JourneyResource;
use App\Http\Resources\StepProgressResource;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Services\Journeys\JourneyService;
use App\Services\Journeys\LegalFormAdvisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class JourneyController extends Controller
{
    public function __construct(private readonly JourneyService $journeys) {}

    public function index(): AnonymousResourceCollection
    {
        return JourneyResource::collection($this->journeys->all());
    }

    public function show(Request $request, Journey $journey): JourneyResource
    {
        return new JourneyResource($this->journeys->withProgress($journey, $request->user('sanctum')));
    }

    /**
     * Parcours suivis par le membre connecté, avec leur avancement.
     */
    public function mine(Request $request): AnonymousResourceCollection
    {
        return JourneyResource::collection($this->journeys->followedBy($request->user()));
    }

    public function start(Request $request, Journey $journey): JourneyResource
    {
        $this->journeys->start($request->user(), $journey);

        return new JourneyResource($this->journeys->withProgress($journey, $request->user()));
    }

    public function leave(Request $request, Journey $journey): Response
    {
        $this->journeys->leave($request->user(), $journey);

        return response()->noContent();
    }

    public function updateStep(UpdateStepProgressRequest $request, JourneyStep $journeyStep): StepProgressResource
    {
        return new StepProgressResource($this->journeys->updateStep(
            $request->user(),
            $journeyStep,
            StepStatus::from($request->validated('status')),
            $request->validated('remind_on'),
            $request->validated('note'),
        ));
    }

    /**
     * Questions du questionnaire « Quelle forme choisir ? » et, avec les trois réponses, l'orientation.
     */
    public function legalForm(LegalFormRequest $request, LegalFormAdvisor $advisor): JsonResponse
    {
        $answers = $request->answers();
        $describe = fn (array $form) => [
            'name' => $form['name'],
            'summary' => $form['summary'],
            'journey_id' => $form['journey']?->id,
        ];

        return response()->json([
            'questions' => LegalFormAdvisor::QUESTIONS,
            'advice' => $answers ? array_map($describe, $advisor->advise($answers)) : null,
        ]);
    }
}
