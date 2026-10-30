<?php

use App\Models\User;
use App\Services\Alerts\SavedSearchService;
use App\Services\Collaboration\MeetingService;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Journeys\JourneyService;
use App\Services\Notifications\WeeklyDigestService;
use App\Services\Opportunities\OpportunityService;
use App\Services\Projects\ProjectService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Minishlink\WebPush\VAPID;

Artisan::command('idee:contact-requests', function (ContactRequestService $requests) {
    $result = $requests->processDeadlines();

    $this->info("{$result['reminded']} relance(s) envoyée(s), {$result['expired']} demande(s) expirée(s).");
})->purpose('Relance à 7 jours et expire à 14 jours les demandes sans réponse (RG-03)');

Artisan::command('idee:dormant-projects', function (ProjectService $projects) {
    $count = $projects->markDormantProjects();

    $this->info("{$count} projet(s) mis en sommeil.");
})->purpose('Met en sommeil les projets sans mise à jour depuis 90 jours (RG-07)');

Artisan::command('idee:step-reminders', function (JourneyService $journeys, OpportunityService $opportunities) {
    $count = $journeys->sendReminders();
    $applications = $opportunities->sendReminders();
    $deadlines = $opportunities->sendDeadlineAlerts();

    $this->info("{$count} rappel(s) d'étape envoyé(s), {$applications} rappel(s) de candidature envoyé(s), {$deadlines} alerte(s) de date limite envoyée(s).");
})->purpose('Envoie les rappels d\'étapes et de candidatures (PG-05) et les alertes avant les dates limites (OP-03)');

Artisan::command('idee:alerts', function (SavedSearchService $searches) {
    $count = $searches->sendAlerts();

    $this->info("{$count} alerte(s) de nouveaux projets envoyée(s).");
})->purpose('Prévient les membres des nouveaux projets qui correspondent à leurs alertes (RR-04)');

Artisan::command('idee:weekly-digest', function (WeeklyDigestService $digests) {
    $count = $digests->send();

    $this->info("{$count} résumé(s) hebdomadaire(s) envoyé(s).");
})->purpose('Envoie le résumé de la semaine par e-mail aux membres à l\'adresse confirmée (NO-04)');

Artisan::command('idee:meeting-reminders', function (MeetingService $meetings) {
    $count = $meetings->sendReminders();

    $this->info("{$count} rendez-vous rappelé(s).");
})->purpose('Rappelle aux deux personnes les rendez-vous confirmés du lendemain (MR-07)');

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

Artisan::command('idee:admin {phone} {--retirer}', function (string $phone) {
    $user = User::where('phone', PhoneNumber::normalize($phone))->first();

    if ($user === null) {
        $this->error('Aucun membre avec ce numéro. Il doit d\'abord se connecter une fois à IDEE.');

        return 1;
    }

    $user->forceFill(['is_admin' => ! $this->option('retirer')])->save();
    $this->info($user->is_admin ? "{$user->name} fait maintenant partie de l'équipe d'administration." : "{$user->name} n'a plus accès à l'administration.");
})->purpose('Donne (ou retire avec --retirer) l\'accès à l\'administration à un membre');

Artisan::command('idee:cles-push', function () {
    $keys = VAPID::createVapidKeys();

    $this->info('Ajoutez ces deux lignes au fichier .env, puis lancez « php artisan optimize » :');
    $this->line("WEBPUSH_PUBLIC_KEY={$keys['publicKey']}");
    $this->line("WEBPUSH_PRIVATE_KEY={$keys['privateKey']}");
})->purpose('Crée les clés des notifications push (NO-01), à faire une seule fois');

// Tâches en arrière-plan (assistant IA) : traitées chaque minute par la tâche planifiée.
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();
Schedule::command('idee:contact-requests')->hourly();
Schedule::command('idee:meeting-reminders')->hourly();
Schedule::command('idee:step-reminders')->dailyAt('08:00');
Schedule::command('idee:alerts')->dailyAt('09:00');
Schedule::command('idee:weekly-digest')->weeklyOn(1, '08:30');
Schedule::command('idee:stale-guides')->weeklyOn(1, '07:00');
Schedule::command('idee:dormant-projects')->dailyAt('03:00');
