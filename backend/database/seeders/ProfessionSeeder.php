<?php

namespace Database\Seeders;

use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Référentiel de départ des métiers (PM-01), complété ensuite depuis
 * l'administration. Relancé à chaque mise en ligne : il ajoute ce qui manque
 * sans jamais écraser un nom modifié ni réactiver un métier désactivé.
 */
class ProfessionSeeder extends Seeder
{
    /**
     * Professions réglementées : identité vérifiée exigée pour afficher le profil (PM-05).
     */
    private const REGULATED = ['Avocat', 'Notaire', 'Expert-comptable', 'Commissaire aux comptes', 'Architecte', 'Huissier de justice'];

    public function run(): void
    {
        $catalogue = [
            'Entrepreneuriat et création d\'entreprise' => ['Porteur de projet', 'Entrepreneur', 'Fondateur', 'Cofondateur', 'Entrepreneur social', 'Entrepreneur individuel', 'Fondateur de startup', 'Repreneur d\'entreprise', 'Porteur de projet communautaire', 'Innovateur', 'Inventeur', 'Agripreneur', 'Porteur de projet culturel', 'Franchisé', 'Franchiseur'],
            'Gestion de projet' => ['Chef de projet', 'Directeur de projet', 'Coordinateur de projet', 'Assistant chef de projet', 'Chargé de projet', 'Responsable de programme', 'Directeur de programme', 'Responsable de portefeuille de projets', 'PMO (bureau des projets)', 'Planificateur de projet', 'Responsable des opérations', 'Responsable du suivi-évaluation', 'Contrôleur de projet', 'Responsable des risques', 'Responsable logistique de projet', 'Consultant en gestion de projet', 'Scrum Master', 'Product Owner', 'Product Manager'],
            'Coaching et mentorat' => ['Coach de projet', 'Coach entrepreneurial', 'Business coach', 'Coach en leadership', 'Coach en développement personnel', 'Coach en gestion d\'entreprise', 'Coach en levée de fonds', 'Coach en pitch', 'Coach en stratégie', 'Coach en innovation', 'Coach en transformation digitale', 'Coach commercial', 'Coach financier', 'Mentor entrepreneurial', 'Mentor professionnel', 'Mentor technique', 'Facilitateur'],
            'Incubation et accompagnement' => ['Directeur d\'incubateur', 'Responsable d\'incubation', 'Chargé d\'accompagnement entrepreneurial', 'Chargé de sélection des projets', 'Chargé de suivi des startups', 'Gestionnaire de cohorte', 'Responsable des partenariats', 'Responsable des relations investisseurs', 'Responsable communauté', 'Conseiller en création d\'entreprise', 'Conseiller en développement d\'entreprise', 'Chargé de développement des entreprises'],
            'Finance et investissement' => ['Investisseur privé', 'Business angel', 'Investisseur institutionnel', 'Capital-risqueur', 'Gestionnaire de fonds', 'Conseiller en investissement', 'Conseiller en financement', 'Analyste financier', 'Analyste crédit', 'Chargé d\'affaires bancaires', 'Chargé de financement', 'Contrôleur de gestion'],
            'Comptabilité et audit' => ['Expert-comptable', 'Commissaire aux comptes', 'Comptable', 'Assistant comptable', 'Auditeur', 'Auditeur qualité'],
            'Juridique et fiscalité' => ['Avocat', 'Notaire', 'Huissier de justice', 'Juriste', 'Conseiller juridique', 'Fiscaliste', 'Conseiller fiscal'],
            'Informatique et développement' => ['Développeur web', 'Développeur mobile', 'Développeur full stack', 'Ingénieur logiciel', 'Architecte logiciel', 'Ingénieur cloud', 'Ingénieur DevOps', 'Directeur technique (CTO)', 'Expert cybersécurité', 'Auditeur sécurité informatique', 'Data analyst', 'Data scientist', 'Ingénieur données', 'Expert blockchain', 'Expert IoT', 'Expert prototypage'],
            'Intelligence artificielle' => ['Consultant en intelligence artificielle', 'Ingénieur en intelligence artificielle', 'Spécialiste de l\'automatisation'],
            'Design' => ['Graphiste', 'Designer UI/UX', 'Product designer', 'Chercheur UX', 'Directeur artistique'],
            'Marketing et communication' => ['Consultant marketing', 'Responsable marketing', 'Chargé de communication', 'Consultant en communication', 'Community manager', 'Spécialiste SEO', 'Créateur de contenu'],
            'Commercial et développement des affaires' => ['Consultant commercial', 'Business developer', 'Agent commercial', 'Représentant commercial', 'Acheteur professionnel'],
            'Stratégie et organisation' => ['Consultant en stratégie', 'Analyste business', 'Consultant en organisation', 'Spécialiste de la conduite du changement', 'Consultant en transformation digitale', 'Responsable innovation'],
            'Ressources humaines et formation' => ['Consultant RH', 'Recruteur', 'Gestionnaire RH', 'Formateur professionnel', 'Consultant pédagogique', 'Ingénieur formation'],
            'Logistique, transport et commerce' => ['Consultant logistique', 'Responsable supply chain', 'Transporteur', 'Logisticien', 'Grossiste', 'Distributeur', 'Détaillant', 'Revendeur', 'Importateur', 'Fournisseur de matériel', 'Fournisseur de matières premières', 'Prestataire de services', 'Sous-traitant'],
            'Agriculture et agroalimentaire' => ['Agronome', 'Ingénieur agricole', 'Conseiller agricole', 'Producteur agricole', 'Ingénieur agroalimentaire'],
            'Industrie et production' => ['Ingénieur industriel', 'Technicien industriel', 'Fabricant', 'Responsable QHSE', 'Responsable qualité'],
            'BTP et immobilier' => ['Architecte', 'Ingénieur génie civil', 'Conducteur de travaux', 'Agent immobilier', 'Expert immobilier', 'Promoteur immobilier'],
            'Export et commerce international' => ['Consultant export', 'Spécialiste du commerce international', 'Exportateur', 'Responsable export', 'Responsable du développement international', 'Facilitateur de partenariats internationaux'],
            'Environnement et développement durable' => ['Expert environnement', 'Consultant en développement durable'],
            'Santé' => ['Consultant santé', 'Expert en santé publique'],
            'Recherche et enseignement' => ['Enseignant-chercheur', 'Chercheur', 'Doctorant', 'Étudiant entrepreneur', 'Chargé d\'études', 'Statisticien', 'Responsable du transfert technologique', 'Responsable de la valorisation de la recherche'],
            'Administration publique et coopération' => ['Agent de l\'administration publique', 'Chargé de programme de développement', 'Chargé de coopération internationale', 'Responsable d\'ONG'],
            'Arts, culture et industries créatives' => ['Artiste', 'Producteur culturel', 'Organisateur d\'événements'],
        ];

        $position = 0;
        foreach ($catalogue as $categoryName => $professions) {
            $category = ProfessionCategory::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'position' => ++$position * 10],
            );

            foreach ($professions as $name) {
                Profession::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['profession_category_id' => $category->id, 'name' => $name, 'regulated' => in_array($name, self::REGULATED, true)],
                );
            }
        }
    }
}
