<?php

namespace App\Services\Assistant;

use App\Contracts\Assistant;
use App\Enums\AssistantStatus;
use App\Exceptions\AssistantUnavailable;
use App\Jobs\GenerateBusinessPlan;
use App\Jobs\GenerateClarification;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Project;
use App\Services\Projects\ReadinessScore;
use Illuminate\Validation\ValidationException;

/**
 * Assistant IA (section 7) : il structure l'idée par des questions (IA-01) et
 * rédige un business plan modifiable (IA-02). Il structure, explique et
 * recommande, mais ne décide jamais à la place du porteur.
 */
class AssistantService
{
    /**
     * Sections du business plan, dans l'ordre de lecture.
     */
    public const SECTIONS = [
        'resume' => 'Résumé',
        'probleme_solution' => 'Problème et solution',
        'marche_clients' => 'Marché et clients',
        'concurrence' => 'Concurrence',
        'modele_economique' => 'Modèle économique',
        'plan_operationnel' => 'Plan opérationnel',
        'equipe' => 'Équipe',
        'financement' => 'Besoins et utilisation des fonds',
        'risques' => 'Risques et réponses',
        'prochaines_etapes' => 'Prochaines étapes',
    ];

    /**
     * Consignes fixes de l'assistant : les lignes rouges du cahier des charges y figurent.
     */
    private const INSTRUCTIONS = <<<'TXT'
        Tu es l'assistant d'IDEE, le réseau des porteurs de projets du Sénégal. Tu aides un porteur à structurer son projet.

        Écris en français simple, avec des phrases courtes, pour un lecteur qui n'est pas spécialiste. Les montants sont en francs CFA (FCFA).
        Appuie-toi uniquement sur ce que le porteur a écrit. Quand une information manque, dis-le et propose ce qu'il faudrait vérifier, au lieu de l'inventer.
        Toute estimation chiffrée est une hypothèse : place-la dans la liste des hypothèses avec ce sur quoi elle repose, pour que le porteur la vérifie.
        Tu structures, expliques et recommandes ; tu ne décides jamais à la place du porteur, d'un investisseur ou d'une institution.
        Ne promets jamais un financement, un rendement ou une rentabilité.
        Ne propose jamais de collecter de l'argent auprès du public, de vendre des parts ou des actions au public, ni de faire transiter de l'argent par IDEE.
        TXT;

    public function __construct(
        private readonly Assistant $assistant,
        private readonly ReadinessScore $readiness,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('idee.ai.enabled');
    }

    /**
     * Lance la préparation de questions pour clarifier l'idée (IA-01).
     *
     * @throws ValidationException
     */
    public function requestClarification(Project $project): IdeaClarification
    {
        $this->ensureEnabled();

        $today = IdeaClarification::where('project_id', $project->id)->where('created_at', '>=', today())->count();
        if ($today >= config('idee.ai.clarifications_per_day')) {
            throw ValidationException::withMessages(['assistant' => 'L\'assistant a déjà posté ses questions aujourd\'hui. Répondez-y, puis revenez demain.']);
        }

        $clarification = new IdeaClarification;
        $clarification->project()->associate($project);
        $clarification->status = AssistantStatus::EnCours;
        $clarification->save();

        GenerateClarification::dispatch($clarification);

        return $clarification;
    }

    public function completeClarification(IdeaClarification $clarification): void
    {
        $this->complete($clarification, function () use ($clarification) {
            $reply = $this->assistant->ask(
                self::INSTRUCTIONS,
                "Voici un projet publié sur IDEE.\n\n".$this->describe($clarification->project)
                    ."\n\nPose de 3 à 5 questions précises qui aideront le porteur à clarifier son idée (clients, prix, coûts, concurrence, première étape), "
                    .'puis donne de 2 à 4 conseils concrets pour avancer. Une phrase par question ou conseil.',
                [
                    'type' => 'object',
                    'properties' => [
                        'questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['questions', 'suggestions'],
                    'additionalProperties' => false,
                ],
                'low',
            );

            return [$reply, [
                'questions' => array_slice(array_values(array_filter($reply->data['questions'] ?? [], 'is_string')), 0, 5),
                'suggestions' => array_slice(array_values(array_filter($reply->data['suggestions'] ?? [], 'is_string')), 0, 4),
            ]];
        });
    }

    /**
     * @param  array<int, string|null>  $answers
     */
    public function answer(IdeaClarification $clarification, array $answers): IdeaClarification
    {
        $clarification->answers = array_map(fn ($answer) => trim((string) $answer), array_slice($answers, 0, count($clarification->questions ?? [])));
        $clarification->save();

        return $clarification;
    }

    /**
     * Lance la rédaction d'une nouvelle version du business plan (IA-02).
     *
     * @throws ValidationException
     */
    public function requestBusinessPlan(Project $project): BusinessPlan
    {
        $this->ensureEnabled();

        $thisMonth = BusinessPlan::where('project_id', $project->id)->where('created_at', '>=', now()->startOfMonth())->count();
        if ($thisMonth >= config('idee.ai.business_plans_per_month')) {
            throw ValidationException::withMessages(['assistant' => 'Vous avez atteint le nombre de business plans générés ce mois-ci. Modifiez la dernière version, ou revenez le mois prochain.']);
        }

        if (BusinessPlan::where('project_id', $project->id)->where('status', AssistantStatus::EnCours)->exists()) {
            throw ValidationException::withMessages(['assistant' => 'Un business plan est déjà en préparation.']);
        }

        $plan = new BusinessPlan;
        $plan->project()->associate($project);
        $plan->version = (int) BusinessPlan::where('project_id', $project->id)->max('version') + 1;
        $plan->status = AssistantStatus::EnCours;
        $plan->save();

        GenerateBusinessPlan::dispatch($plan);

        return $plan;
    }

    public function completeBusinessPlan(BusinessPlan $plan): void
    {
        $this->complete($plan, function () use ($plan) {
            $reply = $this->assistant->ask(
                self::INSTRUCTIONS,
                "Rédige le business plan de ce projet.\n\n".$this->describe($plan->project)
                    ."\n\nÉcris chaque section en 80 à 200 mots. Dans « hypothèses », liste les chiffres que tu as dû estimer "
                    .'(prix, volumes, coûts, délais), avec ce sur quoi repose chaque estimation.',
                [
                    'type' => 'object',
                    'properties' => [
                        'sections' => ['type' => 'array', 'items' => [
                            'type' => 'object',
                            'properties' => [
                                'key' => ['type' => 'string', 'enum' => array_keys(self::SECTIONS)],
                                'content' => ['type' => 'string'],
                            ],
                            'required' => ['key', 'content'],
                            'additionalProperties' => false,
                        ]],
                        'assumptions' => ['type' => 'array', 'items' => [
                            'type' => 'object',
                            'properties' => [
                                'label' => ['type' => 'string'],
                                'value' => ['type' => 'string'],
                                'basis' => ['type' => 'string'],
                            ],
                            'required' => ['label', 'value', 'basis'],
                            'additionalProperties' => false,
                        ]],
                    ],
                    'required' => ['sections', 'assumptions'],
                    'additionalProperties' => false,
                ],
                'high',
            );

            $written = collect($reply->data['sections'] ?? [])->pluck('content', 'key');

            return [$reply, [
                'sections' => collect(self::SECTIONS)->map(fn (string $title, string $key) => [
                    'key' => $key,
                    'title' => $title,
                    'content' => (string) ($written[$key] ?? ''),
                ])->values()->all(),
                'assumptions' => array_values(array_filter($reply->data['assumptions'] ?? [], 'is_array')),
                'generated_at' => now(),
            ]];
        });

        // Un business plan prêt compte dans le score de préparation (RG-06).
        $this->readiness->refresh($plan->project);
    }

    /**
     * Le porteur corrige le texte des sections : c'est son document.
     *
     * @param  array<string, string|null>  $contents  texte par clé de section
     */
    public function editBusinessPlan(BusinessPlan $plan, array $contents): BusinessPlan
    {
        $plan->sections = collect($plan->sections)->map(fn (array $section) => [
            ...$section,
            'content' => array_key_exists($section['key'], $contents) ? trim((string) $contents[$section['key']]) : $section['content'],
        ])->all();
        $plan->edited_at = now();
        $plan->save();

        return $plan;
    }

    /**
     * Ce que l'assistant sait du projet : la fiche, les besoins, les réponses aux questions.
     */
    private function describe(Project $project): string
    {
        $project->loadMissing(['sector', 'openNeeds']);
        $lines = [
            "Titre : {$project->title}",
            "Résumé : {$project->summary}",
            'Détails : '.($project->details ?: 'non précisés'),
            'Secteur : '.($project->sector?->name ?? 'non précisé'),
            'Région : '.($project->region ?: 'non précisée'),
            'Stade : '.$project->stage->label(),
        ];

        foreach ($project->openNeeds as $need) {
            $lines[] = "Besoin ({$need->type->label()}) : {$need->title}. {$need->description}"
                .($need->amount_fcfa ? " Montant recherché : {$need->amount_fcfa} FCFA." : '');
        }

        $clarification = IdeaClarification::where('project_id', $project->id)->whereNotNull('answers')->latest()->first();
        foreach ($clarification?->questions ?? [] as $index => $question) {
            if (filled($clarification->answers[$index] ?? null)) {
                $lines[] = "Question : {$question}\nRéponse du porteur : {$clarification->answers[$index]}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Exécute la demande et enregistre le résultat ou l'échec, avec la consommation.
     *
     * @param  callable(): array{0: AssistantReply, 1: array<string, mixed>}  $work
     */
    private function complete(IdeaClarification|BusinessPlan $record, callable $work): void
    {
        try {
            [$reply, $attributes] = $work();

            $record->forceFill([
                ...$attributes,
                'status' => AssistantStatus::Pret,
                'model' => $reply->model,
                'input_tokens' => $reply->inputTokens,
                'output_tokens' => $reply->outputTokens,
                'error' => null,
            ])->save();
        } catch (AssistantUnavailable $exception) {
            $record->forceFill(['status' => AssistantStatus::Echec, 'error' => $exception->getMessage()])->save();
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw ValidationException::withMessages(['assistant' => 'L\'assistant IA n\'est pas encore activé sur IDEE.']);
        }
    }
}
