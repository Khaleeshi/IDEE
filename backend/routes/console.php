<?php

use App\Services\ContactRequests\ContactRequestService;
use App\Services\Projects\ProjectService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('idee:contact-requests', function (ContactRequestService $requests) {
    $result = $requests->processDeadlines();

    $this->info("{$result['reminded']} relance(s) envoyée(s), {$result['expired']} demande(s) expirée(s).");
})->purpose('Relance à 7 jours et expire à 14 jours les demandes sans réponse (RG-03)');

Artisan::command('idee:dormant-projects', function (ProjectService $projects) {
    $count = $projects->markDormantProjects();

    $this->info("{$count} projet(s) mis en sommeil.");
})->purpose('Met en sommeil les projets sans mise à jour depuis 90 jours (RG-07)');

Schedule::command('idee:contact-requests')->hourly();
Schedule::command('idee:dormant-projects')->dailyAt('03:00');
