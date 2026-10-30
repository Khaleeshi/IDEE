<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    /**
     * Charge le répertoire de départ depuis database/data/opportunites.php,
     * sans écraser les fiches corrigées dans l'administration.
     */
    public function run(): void
    {
        foreach (require database_path('data/opportunites.php') as $data) {
            $opportunity = Opportunity::firstOrNew(['slug' => $data['slug']]);

            if ($opportunity->edited_at === null) {
                $opportunity->fill($data)->save();
            }
        }
    }
}
