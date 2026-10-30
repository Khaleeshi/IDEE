<?php

namespace App\Services\Journeys;

use App\Models\Journey;

/**
 * Questionnaire IDEE « Quelle forme choisir ? » : première étape des parcours
 * de formalisation et de création (cahier des charges, section 5). Il oriente
 * sans remplacer l'avis de l'APIX ou d'un notaire.
 */
class LegalFormAdvisor
{
    /**
     * @var array<string, array{label: string, hint?: string, options: array<string, string>}>
     */
    public const QUESTIONS = [
        'associes' => [
            'label' => 'Vous lancez-vous seul ou avec des associés ?',
            'options' => ['seul' => 'Seul', 'plusieurs' => 'Avec un ou plusieurs associés'],
        ],
        'protection' => [
            'label' => "Voulez-vous séparer vos biens personnels de ceux de l'entreprise ?",
            'hint' => "Avec une société, votre maison ou votre épargne ne répondent pas des dettes de l'entreprise.",
            'options' => ['oui' => 'Oui', 'non' => "Pas pour l'instant"],
        ],
        'taille' => [
            'label' => 'Votre activité reste-t-elle modeste pour le moment ?',
            'hint' => 'Par exemple une vente au marché, un petit atelier ou des services à domicile.',
            'options' => ['petite' => 'Oui, elle reste modeste', 'grande' => 'Non, elle doit vite grandir'],
        ],
    ];

    /**
     * @var array<string, array{name: string, summary: string, journey: string}>
     */
    private const FORMS = [
        'entreprenant' => [
            'name' => "Statut de l'entreprenant",
            'summary' => "Une simple déclaration d'activité, sans immatriculation au registre du commerce : "
                ."la porte d'entrée la plus légère pour sortir de l'informel.",
            'journey' => 'formaliser-simplement',
        ],
        'ei' => [
            'name' => 'Entreprise individuelle',
            'summary' => "Une immatriculation à l'APIX, sans capital ni statuts. Vos biens personnels restent "
                ."engagés en cas de dettes de l'activité.",
            'journey' => 'formaliser-simplement',
        ],
        'suarl' => [
            'name' => 'SUARL (SARL à associé unique)',
            'summary' => 'Une société avec un seul associé, qui protège vos biens personnels. Le notaire est obligatoire.',
            'journey' => 'creer-ma-societe',
        ],
        'sasu' => [
            'name' => 'SASU (SAS à associé unique)',
            'summary' => "Plus souple à organiser qu'une SUARL, utile si vous prévoyez d'accueillir des investisseurs. "
                .'Le notaire est obligatoire.',
            'journey' => 'creer-ma-societe',
        ],
        'sarl' => [
            'name' => 'SARL',
            'summary' => "La société la plus courante entre associés : chacun ne répond des dettes qu'à hauteur de son "
                .'apport. Le notaire est obligatoire.',
            'journey' => 'creer-ma-societe',
        ],
        'sas' => [
            'name' => 'SAS',
            'summary' => "Plus souple qu'une SARL pour répartir le pouvoir et faire entrer des investisseurs. "
                .'Le notaire est obligatoire.',
            'journey' => 'creer-ma-societe',
        ],
    ];

    /**
     * Forme conseillée et solution de rechange, selon les trois réponses.
     *
     * @param  array{associes: string, protection: string, taille: string}  $answers
     * @return array{main: array{name: string, summary: string, journey: ?Journey}, alternative: array{name: string, summary: string, journey: ?Journey}}
     */
    public function advise(array $answers): array
    {
        $growing = $answers['taille'] === 'grande';

        [$main, $alternative] = match (true) {
            $answers['associes'] === 'plusieurs' => $growing ? ['sas', 'sarl'] : ['sarl', 'sas'],
            $answers['protection'] === 'oui' => $growing ? ['sasu', 'suarl'] : ['suarl', 'sasu'],
            ! $growing => ['entreprenant', 'ei'],
            default => ['ei', 'suarl'],
        };

        $journeys = Journey::whereIn('slug', array_column(self::FORMS, 'journey'))->get()->keyBy('slug');
        $describe = fn (string $key) => [
            'name' => self::FORMS[$key]['name'],
            'summary' => self::FORMS[$key]['summary'],
            'journey' => $journeys->get(self::FORMS[$key]['journey']),
        ];

        return ['main' => $describe($main), 'alternative' => $describe($alternative)];
    }
}
