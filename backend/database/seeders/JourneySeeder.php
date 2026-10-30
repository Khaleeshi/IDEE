<?php

namespace Database\Seeders;

use App\Models\Journey;
use App\Models\JourneyStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class JourneySeeder extends Seeder
{
    /**
     * Charge les parcours guidés depuis database/data/parcours.php.
     *
     * Relancé à chaque mise en ligne : les parcours et étapes gardent leur
     * identifiant (et donc l'avancement des membres) tant que leur « slug »
     * ne change pas. Une étape retirée du fichier est supprimée.
     */
    public function run(): void
    {
        $journeys = require database_path('data/parcours.php');
        $related = [];

        foreach ($journeys as $position => $data) {
            $journey = Journey::updateOrCreate(
                ['slug' => $data['slug']],
                [...Arr::except($data, ['slug', 'steps']), 'position' => $position + 1],
            );

            $kept = [];

            foreach ($data['steps'] as $stepPosition => $stepData) {
                $step = $journey->steps()->updateOrCreate(
                    ['slug' => $stepData['slug']],
                    [
                        'title' => $stepData['title'],
                        'body' => $stepData['body'],
                        'documents' => $stepData['documents'] ?? null,
                        'organisation' => $stepData['organisation'] ?? null,
                        'cost' => $stepData['cost'] ?? null,
                        'delay' => $stepData['delay'] ?? null,
                        'source_label' => $stepData['source_label'] ?? null,
                        'source_url' => $stepData['source_url'] ?? null,
                        'checked_on' => $stepData['checked_on'] ?? null,
                        'needs_expert' => $stepData['needs_expert'] ?? false,
                        'action' => $stepData['action'] ?? null,
                        'position' => $stepPosition + 1,
                    ],
                );

                $kept[] = $step->id;
                $related[$step->id] = $stepData['related'] ?? null;
            }

            $journey->steps()->whereNotIn('id', $kept)->delete();
        }

        // Les renvois entre parcours sont posés une fois tous les parcours créés.
        $ids = Journey::pluck('id', 'slug');

        foreach ($related as $stepId => $slug) {
            JourneyStep::whereKey($stepId)->update(['related_journey_id' => $slug ? $ids[$slug] : null]);
        }
    }
}
