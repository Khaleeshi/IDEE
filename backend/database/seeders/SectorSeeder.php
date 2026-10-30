<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SectorSeeder extends Seeder
{
    /**
     * Secteurs d'activité de départ, modifiables ensuite depuis le back-office.
     */
    public function run(): void
    {
        $sectors = [
            'Agriculture',
            'Agro-transformation',
            'Élevage',
            'Pêche et aquaculture',
            'Commerce',
            'Artisanat',
            'Textile et mode',
            'Cosmétique et beauté',
            'Numérique',
            'Bâtiment et travaux publics',
            'Transport et logistique',
            'Énergie',
            'Environnement et recyclage',
            'Santé',
            'Éducation et formation',
            'Tourisme et restauration',
            'Culture et médias',
            'Services aux entreprises',
            'Industrie',
        ];

        foreach ($sectors as $name) {
            Sector::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
