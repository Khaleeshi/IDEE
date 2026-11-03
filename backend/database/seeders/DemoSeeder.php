<?php

namespace Database\Seeders;

use App\Enums\AccompanimentKind;
use App\Enums\ContactRequestStatus;
use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Enums\UserRole;
use App\Models\Profession;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Services\Accompaniments\AccompanimentService;
use App\Services\Accompaniments\ReviewService;
use App\Services\Collaboration\TaskService;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Members\MemberProfileService;
use App\Services\Messaging\MessageService;
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
        // Numéros fixes, pour se connecter facilement sous chaque personne pendant les essais.
        $aminata = User::factory()->create(['name' => 'Aminata (démo)', 'region' => 'Thiès', 'phone' => '+221770000001', 'is_admin' => true]);
        $moussa = User::factory()->create(['name' => 'Moussa (démo)', 'region' => 'Kaolack', 'phone' => '+221770000002']);
        $fatou = User::factory()->identityVerified()->create(['name' => 'Fatou (démo)', 'region' => 'Dakar', 'phone' => '+221770000003', 'skills' => ['Laravel', 'Flutter']]);
        $ibrahima = User::factory()->identityVerified()->create(['name' => 'Ibrahima (démo)', 'region' => 'Paris', 'phone' => '+33600000004']);

        $fatou->syncRoles([UserRole::Contributeur, UserRole::Coach]);
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

        $this->lots1And2($aminata, $fatou, $ibrahima, $cosmetics);
    }

    /**
     * Lots 1 et 2 : métiers, accompagnement avec avis, demandes, tâches datées.
     */
    private function lots1And2(User $aminata, User $fatou, User $ibrahima, Project $cosmetics): void
    {
        $profiles = app(MemberProfileService::class);
        $profession = fn (string $slug) => Profession::where('slug', $slug)->value('id');

        $coach = $profiles->create($fatou, [
            'profession_id' => $profession('coach-entrepreneurial'),
            'description' => 'J\'aide les porteurs à structurer leur offre et à trouver leurs premiers clients.',
            'experience_years' => 7, 'offers_mentoring' => true, 'offers_volunteer' => true,
        ]);
        $profiles->create($fatou, [
            'profession_id' => $profession('developpeur-mobile'),
            'description' => 'Applications Flutter : une demi-journée par mois offerte aux projets à impact.',
            'offers_paid' => true, 'offers_volunteer' => true, 'rate' => '25 000 FCFA la demi-journée',
        ]);
        $profiles->create($ibrahima, [
            'profession_id' => $profession('business-angel'),
            'description' => 'J\'investis dans les marques africaines de beauté et d\'agroalimentaire, depuis Paris.',
            'offers_mentoring' => true,
        ]);

        $accompaniments = app(AccompanimentService::class);
        $mentorship = $accompaniments->request($aminata, $coach, AccompanimentKind::Mentorat,
            'Je lance une marque de cosmétiques au karité à Thiès : j\'ai besoin d\'aide pour fixer mes prix et trouver des salons de beauté revendeurs.', $cosmetics);
        $accompaniments->accept($mentorship);
        $accompaniments->addSession($mentorship, $fatou, now()->subDays(14), 'Coût de revient : 2 300 FCFA le pot. Prix de vente fixé à 4 500 FCFA.', 'Lister 10 salons de beauté à Thiès.');
        $accompaniments->addSession($mentorship, $aminata, now()->subDays(3), 'Trois salons partants pour un dépôt-vente.', 'Préparer un présentoir.');
        $accompaniments->complete($mentorship, $fatou);
        $review = app(ReviewService::class)->forAccompaniment($mentorship, $aminata, 5, 'Très concret : en deux séances j\'avais mes prix et mes premiers revendeurs.');
        app(ReviewService::class)->reply($review, $fatou, 'Merci Aminata, bravo pour ta persévérance !');

        $requests = app(ContactRequestService::class);
        $request = $requests->send($ibrahima, $aminata, 'Bonjour Aminata, je vis à Paris et j\'investis dans des marques de beauté africaines. Votre test auprès de 40 clientes m\'intéresse : pouvons-nous parler de votre besoin de financement ?', $cosmetics);
        $requests->respond($request, $aminata, ContactRequestStatus::Interesse);
        app(MessageService::class)->send($request, $aminata, 'Bonjour Ibrahima, merci. Je peux vous envoyer le détail des coûts de production cette semaine.');

        $tasks = app(TaskService::class);
        $tasks->create($cosmetics, $aminata, 'Commander 500 pots et étiquettes', now()->addDays(4));
        $tasks->create($cosmetics, $aminata, 'Déposer le présentoir au salon Diarra', now()->addDays(12));
        $tasks->create($cosmetics, $aminata, 'Relancer le fournisseur de karité', now()->subDays(2));
    }
}
