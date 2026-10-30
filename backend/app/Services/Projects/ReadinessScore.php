<?php

namespace App\Services\Projects;

use App\Enums\AssistantStatus;
use App\Enums\ProjectStage;
use App\Models\BusinessPlan;
use App\Models\Project;

/**
 * Score de préparation d'un projet (PR-06, RG-06). Il repose sur des
 * critères publics et ne mesure jamais la rentabilité d'un projet.
 */
class ReadinessScore
{
    /**
     * @return array{score: int, missing: list<array{key: string, advice: string, points: int}>}
     */
    public function evaluate(Project $project): array
    {
        $project->loadMissing(['owner', 'openNeeds']);

        $criteria = [
            [
                'key' => 'summary',
                'points' => 10,
                'met' => mb_strlen($project->summary) >= 200,
                'advice' => 'Rédigez un résumé public d\'au moins 200 caractères : problème, solution, clients.',
            ],
            [
                'key' => 'details',
                'points' => 10,
                'met' => mb_strlen((string) $project->details) >= 300,
                'advice' => 'Ajoutez des détails privés d\'au moins 300 caractères pour les personnes que vous acceptez.',
            ],
            [
                'key' => 'sector',
                'points' => 5,
                'met' => $project->sector_id !== null,
                'advice' => 'Indiquez le secteur d\'activité.',
            ],
            [
                'key' => 'region',
                'points' => 5,
                'met' => filled($project->region),
                'advice' => 'Indiquez la région où le projet se déroule.',
            ],
            [
                'key' => 'stage',
                'points' => 10,
                'met' => $project->stage !== ProjectStage::Idee,
                'advice' => 'Faites avancer le projet au-delà de l\'idée : validez-la auprès de clients.',
            ],
            [
                'key' => 'needs',
                'points' => 15,
                'met' => $project->openNeeds->isNotEmpty(),
                'advice' => 'Publiez au moins un appel à action : ce qui manque au projet.',
            ],
            [
                'key' => 'phone',
                'points' => 10,
                'met' => $project->owner->phone_verified_at !== null || $project->owner->email_verified_at !== null,
                'advice' => 'Vérifiez votre numéro de téléphone ou votre adresse e-mail.',
            ],
            [
                'key' => 'identity',
                'points' => 10,
                'met' => $project->owner->hasVerifiedIdentity(),
                'advice' => 'Faites vérifier votre identité pour rassurer vos interlocuteurs.',
            ],
            [
                'key' => 'team',
                'points' => 10,
                'met' => $project->team()->exists(),
                'advice' => 'Présentez votre équipe : au moins une personne en plus de vous, avec son rôle.',
            ],
            [
                'key' => 'business_plan',
                'points' => 15,
                'met' => BusinessPlan::where('project_id', $project->id)->where('status', AssistantStatus::Pret)->exists(),
                'advice' => 'Préparez votre business plan avec l\'assistant, puis relisez-le.',
            ],
        ];

        $score = 0;
        $missing = [];

        foreach ($criteria as $criterion) {
            if ($criterion['met']) {
                $score += $criterion['points'];
            } else {
                $missing[] = [
                    'key' => $criterion['key'],
                    'advice' => $criterion['advice'],
                    'points' => $criterion['points'],
                ];
            }
        }

        return ['score' => $score, 'missing' => $missing];
    }

    public function refresh(Project $project): void
    {
        $project->forceFill(['readiness_score' => $this->evaluate($project)['score']])->save();
    }
}
