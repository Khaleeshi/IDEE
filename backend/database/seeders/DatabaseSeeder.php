<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([SectorSeeder::class, ProfessionSeeder::class, JourneySeeder::class, OpportunitySeeder::class]);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
            $this->command?->info('Comptes de démonstration (le code de connexion est écrit dans storage/logs/laravel.log) :');
            $this->command?->table(['Nom', 'Numéro', 'À essayer'], [
                ['Aminata', '77 000 00 01', 'porteuse de projet, administratrice'],
                ['Moussa', '77 000 00 02', 'porteur de projet'],
                ['Fatou', '77 000 00 03', 'coach, mentore, développeuse'],
                ['Ibrahima', '+33 6 00 00 00 04', 'investisseur de la diaspora'],
            ]);
        }
    }
}
