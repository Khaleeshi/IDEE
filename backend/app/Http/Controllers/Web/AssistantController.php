<?php

namespace App\Http\Controllers\Web;

use App\Enums\AssistantDocumentKind;
use App\Http\Controllers\Controller;
use App\Models\AssistantDocument;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Meeting;
use App\Models\Project;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Assistant IA : questions pour clarifier l'idée (IA-01), business plan (IA-02),
 * préparation des rendez-vous (IA-05) et présentation du projet (IA-06).
 */
class AssistantController extends Controller
{
    public function __construct(private readonly AssistantService $assistant) {}

    public function clarify(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->assistant->requestClarification($project);

        return redirect()->to(route('projects.show', $project).'#assistant')
            ->with('status', 'L\'assistant prépare ses questions : elles apparaissent ici dans un instant.');
    }

    public function answer(Request $request, IdeaClarification $ideaClarification): RedirectResponse
    {
        Gate::authorize('update', $ideaClarification->project);
        $answers = $request->validate(['answers' => ['array'], 'answers.*' => ['nullable', 'string', 'max:2000']])['answers'] ?? [];

        $this->assistant->answer($ideaClarification, $answers);

        return redirect()->to(route('projects.show', $ideaClarification->project).'#assistant')
            ->with('status', 'Réponses enregistrées : l\'assistant s\'en servira pour votre business plan.');
    }

    public function generate(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->assistant->requestBusinessPlan($project);

        return redirect()->route('business-plans.show', $project);
    }

    public function show(Project $project): View
    {
        Gate::authorize('update', $project);

        $plans = BusinessPlan::where('project_id', $project->id)->orderByDesc('version')->get();

        return view('business-plans.show', [
            'project' => $project,
            'plan' => $plans->first(),
            'versions' => $plans,
            'enabled' => $this->assistant->isEnabled(),
        ]);
    }

    public function update(Request $request, BusinessPlan $businessPlan): RedirectResponse
    {
        Gate::authorize('update', $businessPlan->project);
        $contents = $request->validate(['sections' => ['array'], 'sections.*' => ['nullable', 'string', 'max:6000']])['sections'] ?? [];

        $this->assistant->editBusinessPlan($businessPlan, $contents);

        return redirect()->route('business-plans.show', $businessPlan->project)->with('status', 'Business plan enregistré.');
    }

    /**
     * Version à imprimer ou à enregistrer en PDF depuis le navigateur.
     */
    public function print(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('business-plans.print', [
            'project' => $project,
            'plan' => BusinessPlan::where('project_id', $project->id)->where('status', 'pret')->orderByDesc('version')->firstOrFail(),
        ]);
    }

    /**
     * IA-05 : préparation d'un rendez-vous pour la personne qui la demande.
     */
    public function prepareMeeting(Request $request, Meeting $meeting): RedirectResponse
    {
        Gate::authorize('cancel', $meeting);

        $document = $this->assistant->requestMeetingPreparation($meeting, $request->user());

        return redirect()->route('assistant.document', $document);
    }

    /**
     * Préparation de rendez-vous, visible seulement de la personne qui l'a demandée.
     */
    public function document(Request $request, AssistantDocument $assistantDocument): View|RedirectResponse
    {
        abort_unless($assistantDocument->user_id === $request->user()->id, 404);

        if ($assistantDocument->kind === AssistantDocumentKind::Presentation) {
            return redirect()->route('pitch-decks.show', $assistantDocument->project);
        }

        return view('assistant.preparation', ['document' => $assistantDocument->load('meeting.contactRequest')]);
    }

    /**
     * IA-06 : présentation du projet, dernière version et historique.
     */
    public function pitchDeck(Project $project): View
    {
        Gate::authorize('update', $project);

        $decks = AssistantDocument::where('kind', AssistantDocumentKind::Presentation)->where('project_id', $project->id)->orderByDesc('version')->get();

        return view('pitch-decks.show', [
            'project' => $project,
            'deck' => $decks->first(),
            'enabled' => $this->assistant->isEnabled(),
        ]);
    }

    public function generatePitchDeck(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->assistant->requestPitchDeck($project, $request->user());

        return redirect()->route('pitch-decks.show', $project);
    }

    public function updatePitchDeck(Request $request, AssistantDocument $assistantDocument): RedirectResponse
    {
        abort_unless($assistantDocument->kind === AssistantDocumentKind::Presentation, 404);
        Gate::authorize('update', $assistantDocument->project);
        $slides = $request->validate([
            'slides' => ['array'],
            'slides.*.title' => ['nullable', 'string', 'max:200'],
            'slides.*.bullets' => ['nullable', 'string', 'max:2000'],
            'slides.*.notes' => ['nullable', 'string', 'max:3000'],
        ])['slides'] ?? [];

        $this->assistant->editPitchDeck($assistantDocument, $slides);

        return redirect()->route('pitch-decks.show', $assistantDocument->project)->with('status', 'Présentation enregistrée.');
    }

    public function printPitchDeck(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('pitch-decks.print', [
            'project' => $project,
            'deck' => AssistantDocument::where('kind', AssistantDocumentKind::Presentation)
                ->where('project_id', $project->id)
                ->where('status', 'pret')
                ->orderByDesc('version')
                ->firstOrFail(),
        ]);
    }
}
