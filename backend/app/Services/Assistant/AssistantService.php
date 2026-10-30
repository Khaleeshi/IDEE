<?php

namespace App\Services\Assistant;

use App\Contracts\Assistant;
use App\Enums\AssistantDocumentKind;
use App\Enums\AssistantStatus;
use App\Enums\MeetingStatus;
use App\Enums\UserRole;
use App\Exceptions\AssistantUnavailable;
use App\Jobs\GenerateAssistantDocument;
use App\Jobs\GenerateBusinessPlan;
use App\Jobs\GenerateClarification;
use App\Models\AssistantDocument;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use App\Services\Projects\ReadinessScore;
use Illuminate\Validation\ValidationException;

/**
 * Assistant IA (section 7) : il structure l'idée par des questions (IA-01),
 * rédige un business plan modifiable (IA-02), prépare les rendez-vous (IA-05)
 * et la présentation du projet (IA-06). Il structure, explique et recommande,
 * mais ne décide jamais à la place du porteur.
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
     * Diapositives de la présentation du projet (IA-06), dans l'ordre.
     */
    public const SLIDES = [
        'probleme' => 'Le problème',
        'solution' => 'Notre solution',
        'marche' => 'Le marché et les clients',
        'modele' => 'Comment le projet gagne de l\'argent',
        'avancement' => 'Où nous en sommes',
        'concurrence' => 'Concurrence et différence',
        'equipe' => 'L\'équipe',
        'besoins' => 'Ce que nous cherchons',
        'suite' => 'Prochaines étapes',
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
     * Lance la préparation d'un rendez-vous pour l'une des deux personnes (IA-05).
     *
     * @throws ValidationException
     */
    public function requestMeetingPreparation(Meeting $meeting, User $user): AssistantDocument
    {
        $this->ensureEnabled();

        if (! in_array($meeting->status, [MeetingStatus::Proposee, MeetingStatus::Confirmee], true) || $meeting->starts_at->isPast()) {
            throw ValidationException::withMessages(['assistant' => 'Ce rendez-vous n\'est plus à préparer.']);
        }

        $today = AssistantDocument::where('kind', AssistantDocumentKind::PreparationRendezVous)
            ->whereBelongsTo($user)
            ->where('created_at', '>=', today())
            ->count();
        if ($today >= config('idee.ai.meeting_preparations_per_day')) {
            throw ValidationException::withMessages(['assistant' => 'Vous avez atteint le nombre de préparations du jour. Revenez demain.']);
        }

        return $this->startDocument(AssistantDocumentKind::PreparationRendezVous, $user, $meeting->contactRequest->project, $meeting);
    }

    /**
     * Lance la rédaction d'une présentation du projet (IA-06).
     *
     * @throws ValidationException
     */
    public function requestPitchDeck(Project $project, User $owner): AssistantDocument
    {
        $this->ensureEnabled();

        $decks = AssistantDocument::where('kind', AssistantDocumentKind::Presentation)->where('project_id', $project->id);

        if ((clone $decks)->where('created_at', '>=', now()->startOfMonth())->count() >= config('idee.ai.pitch_decks_per_month')) {
            throw ValidationException::withMessages(['assistant' => 'Vous avez atteint le nombre de présentations générées ce mois-ci. Modifiez la dernière version, ou revenez le mois prochain.']);
        }

        if ((clone $decks)->where('status', AssistantStatus::EnCours)->exists()) {
            throw ValidationException::withMessages(['assistant' => 'Une présentation est déjà en préparation.']);
        }

        return $this->startDocument(AssistantDocumentKind::Presentation, $owner, $project, version: (int) $decks->max('version') + 1);
    }

    public function completeDocument(AssistantDocument $document): void
    {
        $this->complete($document, fn () => match ($document->kind) {
            AssistantDocumentKind::PreparationRendezVous => $this->writeMeetingPreparation($document),
            AssistantDocumentKind::Presentation => $this->writePitchDeck($document),
        });
    }

    /**
     * Le porteur corrige les diapositives : titres, points et notes.
     *
     * @param  array<string, array{title?: string|null, bullets?: string|null, notes?: string|null}>  $slides
     */
    public function editPitchDeck(AssistantDocument $deck, array $slides): AssistantDocument
    {
        $deck->content = [
            ...$deck->content,
            'slides' => collect($deck->content['slides'] ?? [])->map(function (array $slide) use ($slides) {
                $input = $slides[$slide['key']] ?? null;

                return $input === null ? $slide : [
                    ...$slide,
                    'title' => trim((string) ($input['title'] ?? $slide['title'])),
                    'bullets' => array_values(array_filter(array_map('trim', explode("\n", (string) ($input['bullets'] ?? ''))))),
                    'notes' => trim((string) ($input['notes'] ?? '')),
                ];
            })->all(),
        ];
        $deck->edited_at = now();
        $deck->save();

        return $deck;
    }

    /**
     * @return array{0: AssistantReply, 1: array<string, mixed>}
     */
    private function writeMeetingPreparation(AssistantDocument $document): array
    {
        $meeting = $document->meeting;
        $conversation = $meeting->contactRequest;
        $user = $document->user;
        $other = $conversation->otherParty($user);

        $context = [
            'Rendez-vous le '.$meeting->starts_at->translatedFormat('d F Y à H\hi').', '.mb_strtolower($meeting->mode->label()).'.',
            'Ordre du jour : '.($meeting->agenda ?: 'non précisé'),
            "Vous préparez ce rendez-vous pour {$user->name}.",
            "Son interlocuteur : {$other?->name}".($other ? $this->publicProfile($other) : ''),
            'Premier message de la conversation : '.$conversation->message,
        ];

        foreach ($conversation->agreements()->get() as $agreement) {
            $context[] = "Étape convenue ({$agreement->status->label()}) : {$agreement->objective}, avant le {$agreement->due_on->translatedFormat('d F')}.";
        }

        if ($conversation->project) {
            // Les détails privés du projet ne sont donnés qu'à son porteur.
            $context[] = "Projet concerné :\n".$this->describe($conversation->project, private: $conversation->project->isOwnedBy($user));
        }

        $reply = $this->assistant->ask(
            self::INSTRUCTIONS,
            "Aide à préparer ce rendez-vous.\n\n".implode("\n", $context)
                ."\n\nDonne l'objectif du rendez-vous en une phrase, 4 à 6 questions à poser, 3 à 5 points à présenter, "
                .'les documents ou chiffres à apporter, et 2 à 4 prochaines étapes à proposer en fin de rendez-vous. Une phrase par élément.',
            [
                'type' => 'object',
                'properties' => [
                    'objective' => ['type' => 'string'],
                    'questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'talking_points' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'to_bring' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'next_steps' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'required' => ['objective', 'questions', 'talking_points', 'to_bring', 'next_steps'],
                'additionalProperties' => false,
            ],
            'low',
        );

        $list = fn (string $key, int $max) => array_slice(array_values(array_filter($reply->data[$key] ?? [], 'is_string')), 0, $max);

        return [$reply, ['content' => [
            'objective' => (string) ($reply->data['objective'] ?? ''),
            'questions' => $list('questions', 6),
            'talking_points' => $list('talking_points', 5),
            'to_bring' => $list('to_bring', 5),
            'next_steps' => $list('next_steps', 4),
        ]]];
    }

    /**
     * @return array{0: AssistantReply, 1: array<string, mixed>}
     */
    private function writePitchDeck(AssistantDocument $document): array
    {
        $reply = $this->assistant->ask(
            self::INSTRUCTIONS."\nUne présentation s'adresse à des partenaires et investisseurs rencontrés un par un : elle ne constitue jamais une offre au public.",
            "Rédige la présentation de ce projet, en diapositives.\n\n".$this->describe($document->project)
                ."\n\nPour chaque diapositive : un titre court, 2 à 4 points de 15 mots au plus, et des notes de 2 à 4 phrases pour l'oral. "
                .'Quand un chiffre manque, écris « à compléter » plutôt que de l\'inventer.',
            [
                'type' => 'object',
                'properties' => [
                    'tagline' => ['type' => 'string'],
                    'slides' => ['type' => 'array', 'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'string', 'enum' => array_keys(self::SLIDES)],
                            'title' => ['type' => 'string'],
                            'bullets' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'notes' => ['type' => 'string'],
                        ],
                        'required' => ['key', 'title', 'bullets', 'notes'],
                        'additionalProperties' => false,
                    ]],
                ],
                'required' => ['tagline', 'slides'],
                'additionalProperties' => false,
            ],
            'medium',
        );

        $written = collect($reply->data['slides'] ?? [])->keyBy('key');

        return [$reply, ['content' => [
            'tagline' => (string) ($reply->data['tagline'] ?? ''),
            'slides' => collect(self::SLIDES)->map(fn (string $title, string $key) => [
                'key' => $key,
                'title' => (string) ($written[$key]['title'] ?? $title),
                'bullets' => array_slice(array_values(array_filter($written[$key]['bullets'] ?? [], 'is_string')), 0, 4),
                'notes' => (string) ($written[$key]['notes'] ?? ''),
            ])->values()->all(),
        ]]];
    }

    private function startDocument(AssistantDocumentKind $kind, User $user, ?Project $project, ?Meeting $meeting = null, int $version = 1): AssistantDocument
    {
        $document = new AssistantDocument;
        $document->kind = $kind;
        $document->user()->associate($user);
        $document->project()->associate($project);
        $document->meeting()->associate($meeting);
        $document->version = $version;
        $document->status = AssistantStatus::EnCours;
        $document->save();

        GenerateAssistantDocument::dispatch($document);

        return $document;
    }

    /**
     * Profil public d'un membre : rôles, présentation, services. Jamais ses coordonnées.
     */
    private function publicProfile(User $user): string
    {
        $roles = collect($user->publicRoles())->map(fn (UserRole $role) => $role->label())->join(', ');

        return ($roles ? " ({$roles})" : '')
            .($user->bio ? ". Présentation : {$user->bio}" : '')
            .($user->expertise ? ". Services : {$user->expertise}" : '');
    }

    /**
     * Ce que l'assistant sait du projet : la fiche, les besoins, les réponses aux questions.
     * Les détails privés ne sont donnés que pour le porteur lui-même.
     */
    private function describe(Project $project, bool $private = true): string
    {
        $project->loadMissing(['sector', 'openNeeds']);
        $lines = [
            "Titre : {$project->title}",
            "Résumé : {$project->summary}",
            'Détails : '.($private ? ($project->details ?: 'non précisés') : 'confidentiels'),
            'Secteur : '.($project->sector?->name ?? 'non précisé'),
            'Région : '.($project->region ?: 'non précisée'),
            'Stade : '.$project->stage->label(),
        ];

        foreach ($project->openNeeds as $need) {
            $lines[] = "Besoin ({$need->type->label()}) : {$need->title}. {$need->description}"
                .($need->amount_fcfa ? " Montant recherché : {$need->amount_fcfa} FCFA." : '');
        }

        $clarification = $private ? IdeaClarification::where('project_id', $project->id)->whereNotNull('answers')->latest()->first() : null;
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
    private function complete(IdeaClarification|BusinessPlan|AssistantDocument $record, callable $work): void
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

    /**
     * Efface les échanges du membre avec l'assistant : questions sur ses idées et
     * préparations de rendez-vous (section 7, règle 8). Les business plans et
     * présentations restent : ce sont ses documents, qui partent avec son projet.
     */
    public function forgetConversations(User $user): int
    {
        $clarifications = IdeaClarification::whereIn('project_id', $user->projects()->withTrashed()->select('id'))->delete();
        $preparations = AssistantDocument::whereBelongsTo($user)->where('kind', AssistantDocumentKind::PreparationRendezVous)->delete();

        return $clarifications + $preparations;
    }
}
