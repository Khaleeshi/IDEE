<?php

namespace App\Http\Controllers\Web;

use App\Enums\StepStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LegalFormRequest;
use App\Http\Requests\UpdateStepProgressRequest;
use App\Models\Journey;
use App\Models\JourneyStep;
use App\Services\Journeys\JourneyService;
use App\Services\Journeys\LegalFormAdvisor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JourneyController extends Controller
{
    public function __construct(private readonly JourneyService $journeys) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('journeys.index', [
            'journeys' => $this->journeys->all(),
            'followed' => $user ? $this->journeys->followedBy($user) : collect(),
            'reminders' => $user ? $this->journeys->dueReminders($user) : collect(),
        ]);
    }

    public function show(Request $request, Journey $journey): View
    {
        $journey->load('steps.relatedJourney');
        $user = $request->user();
        $progress = $this->journeys->progressFor($user, $journey);

        return view('journeys.show', [
            'journey' => $journey,
            'progress' => $progress,
            'following' => $progress->isNotEmpty(),
            'doneCount' => $progress->filter(fn ($item) => $item->status->isDone())->count(),
            'helpProject' => $user ? $this->journeys->helpTarget($user) : null,
            'statuses' => StepStatus::cases(),
            // PG-07 : fiches en wolof quand l'équipe les a traduites.
            'wolof' => $request->query('langue') === 'wo',
            'hasWolof' => $journey->steps->contains(fn ($step) => $step->hasWolof() || $step->audio_path),
        ]);
    }

    public function legalForm(LegalFormRequest $request, LegalFormAdvisor $advisor): View
    {
        $answers = $request->answers();

        return view('journeys.legal-form', [
            'questions' => LegalFormAdvisor::QUESTIONS,
            'answers' => $answers ?? [],
            'advice' => $answers ? $advisor->advise($answers) : null,
        ]);
    }

    public function start(Request $request, Journey $journey): RedirectResponse
    {
        $this->journeys->start($request->user(), $journey);

        return redirect()->route('journeys.show', $journey)
            ->with('status', 'Parcours commencé. Avancez à votre rythme et indiquez où vous en êtes à chaque étape.');
    }

    public function leave(Request $request, Journey $journey): RedirectResponse
    {
        $this->journeys->leave($request->user(), $journey);

        return redirect()->route('journeys.index')->with('status', 'Ce parcours a été retiré de vos démarches.');
    }

    public function updateStep(UpdateStepProgressRequest $request, JourneyStep $journeyStep): RedirectResponse
    {
        $this->journeys->updateStep(
            $request->user(),
            $journeyStep,
            StepStatus::from($request->validated('status')),
            $request->validated('remind_on'),
            $request->validated('note'),
        );

        return redirect()->to(route('journeys.show', $journeyStep->journey_id).'#etape-'.$journeyStep->id)
            ->with('status', 'Étape mise à jour.');
    }
}
