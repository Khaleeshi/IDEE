<?php

use App\Services\ContactRequests\ContactRequestService;
use App\Services\Journeys\JourneyService;
use App\Services\Projects\ProjectService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('idee:contact-requests', function (ContactRequestService $requests) {
    $result = $requests->processDeadlines();

    $this->info("{$result['reminded']} relance(s) envoyée(s), {$result['expired']} demande(s) expirée(s).");
})->purpose('Relance à 7 jours et expire à 14 jours les demandes sans réponse (RG-03)');

Artisan::command('idee:dormant-projects', function (ProjectService $projects) {
    $count = $projects->markDormantProjects();

    $this->info("{$count} projet(s) mis en sommeil.");
})->purpose('Met en sommeil les projets sans mise à jour depuis 90 jours (RG-07)');

Artisan::command('idee:step-reminders', function (JourneyService $journeys) {
    $count = $journeys->sendReminders();

    $this->info("{$count} rappel(s) d'étape envoyé(s).");
})->purpose('Envoie les rappels fixés par les membres sur leurs étapes de parcours (PG-05)');

Artisan::command('idee:stale-guides', function (JourneyService $journeys) {
    $steps = $journeys->staleSteps();

    foreach ($steps as $step) {
        $this->warn("{$step->journey->title} › {$step->title} : source consultée le ".($step->checked_on?->format('d/m/Y') ?? 'jamais'));
    }

    if ($steps->isNotEmpty()) {
        Log::warning("{$steps->count()} fiche(s) pratique(s) à revoir (RG-10).", ['steps' => $steps->pluck('id')->all()]);
    }

    $this->info("{$steps->count()} fiche(s) à revoir.");
})->purpose('Signale les fiches pratiques non revues depuis 6 mois (RG-10)');

Schedule::command('idee:contact-requests')->hourly();
Schedule::command('idee:step-reminders')->dailyAt('08:00');
Schedule::command('idee:stale-guides')->weeklyOn(1, '07:00');
Schedule::command('idee:dormant-projects')->dailyAt('03:00');
