<?php

namespace Database\Seeders;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Enums\UserRole;
use App\Models\Sector;
use App\Models\User;
use App\Services\Needs\NeedService;
use App\Services\Projects\ProjectService;
use Illuminate\Database\Seeder;

/**
 * Données fictives pour essayer l'application en local. Les personnes et
 * les projets reprennent les personas du cahier des charges (section 4).
 */
class DemoSeeder extends Seeder
{
    public function run(ProjectService $projects, NeedService $needs): void
    {
        $aminata = User::factory()->create(['name' => 'Aminata (démo)', 'region' => 'Thiès']);
        $moussa = User::factory()->create(['name' => 'Moussa (démo)', 'region' => 'Kaolack']);
        $fatou = User::factory()->create(['name' => 'Fatou (démo)', 'region' => 'Dakar', 'skills' => ['Laravel', 'Flutter']]);
        $ibrahima = User::factory()->identityVerified()->create(['name' => 'Ibrahima (démo)', 'region' => 'Paris']);

        $fatou->syncRoles([UserRole::Contributeur]);
        $ibrahima->syncRoles([UserRole::Investisseur]);

        $cosmetics = $projects->create($aminata, [
            'title' => 'Marque de cosmétiques naturels au karité',
            'summary' => 'Une gamme de soins pour la peau fabriqués à Thiès à partir de karité et d\'huile de baobab, vendue en ligne et dans les salons de beauté. Les premiers tests auprès de 40 clientes sont positifs.',
            'details' => 'Coûts de production, fournisseurs de karité identifiés à Kédougou, prix de vente visé de 4 500 FCFA le pot de 200 ml.',
            'region' => 'Thiès',
            'stage' => ProjectStage::Validation,
            'sector_id' => Sector::where('slug', 'cosmetique-et-beaute')->value('id'),
        ]);

        $needs->create($cosmetics, [
            'type' => NeedType::Financement,
            'title' => 'Financer le premier lot de production',
            'description' => 'Achat de matières premières, d\'emballages et d\'une petite cuve de mélange pour produire 1 000 pots.',
            'amount_fcfa' => 5_000_000,
            'counterpart' => 'Prêt remboursable sur 18 mois ou entrée au capital à discuter.',
        ]);

        $cereals = $projects->create($moussa, [
            'title' => 'Transformation de céréales locales',
            'summary' => 'Production de farines de mil et de maïs enrichies pour les boutiques de quartier de Kaolack.',
            'region' => 'Kaolack',
            'stage' => ProjectStage::Lancement,
            'sector_id' => Sector::where('slug', 'agro-transformation')->value('id'),
        ]);

        $needs->create($cereals, [
            'type' => NeedType::Clients,
            'title' => 'Trouver des distributeurs à Dakar',
            'description' => 'Je cherche des grossistes ou des épiceries à Dakar pour écouler 2 tonnes de farine par mois.',
        ]);
    }
}
