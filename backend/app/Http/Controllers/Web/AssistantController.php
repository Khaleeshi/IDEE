<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Project;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Assistant IA du porteur : questions pour clarifier l'idée (IA-01) et business plan (IA-02).
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
}
