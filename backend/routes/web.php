<?php

use App\Http\Controllers\Web\Admin\AdminController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\AssistantController;
use App\Http\Controllers\Web\CollaborationController;
use App\Http\Controllers\Web\ContactRequestController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\FeedController;
use App\Http\Controllers\Web\FollowController;
use App\Http\Controllers\Web\JourneyController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MemberController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\NeedController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OpportunityController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\PushSubscriptionController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TrustController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FeedController::class, 'index'])->name('home');
Route::view('/hors-ligne', 'offline')->name('offline');

// Connexion par code à usage unique.
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'sendCode'])->middleware('throttle:otp')->name('login.send');
    Route::get('/connexion/code', [LoginController::class, 'codeForm'])->name('login.code');
    Route::post('/connexion/code', [LoginController::class, 'verify'])->middleware('throttle:otp-verify')->name('login.verify');
});
Route::post('/deconnexion', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/mes-projets', [ProjectController::class, 'mine'])->name('projects.mine');
    Route::get('/projets/nouveau', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projets', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projets/{project}/modifier', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projets/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projets/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('/projets/{project}/mises-a-jour', [ProjectController::class, 'publishUpdate'])->name('projects.updates.store');
    Route::post('/projets/{project}/equipe', [ProjectController::class, 'addTeamMember'])->name('projects.team.store');
    Route::delete('/equipe/{projectMember}', [ProjectController::class, 'removeTeamMember'])->name('projects.team.destroy');
    Route::post('/projets/{project}/besoins', [NeedController::class, 'store'])->name('needs.store');
    Route::post('/projets/{project}/assistant/questions', [AssistantController::class, 'clarify'])->name('assistant.clarify');
    Route::put('/clarifications/{ideaClarification}', [AssistantController::class, 'answer'])->name('assistant.answer');
    Route::get('/projets/{project}/business-plan', [AssistantController::class, 'show'])->name('business-plans.show');
    Route::post('/projets/{project}/business-plan', [AssistantController::class, 'generate'])->name('business-plans.generate');
    Route::get('/projets/{project}/business-plan/impression', [AssistantController::class, 'print'])->name('business-plans.print');
    Route::put('/business-plans/{businessPlan}', [AssistantController::class, 'update'])->name('business-plans.update');
    Route::post('/projets/{project}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{projectDocument}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{projectDocument}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/projets/{project}/acces-documents', [DocumentController::class, 'requestAccess'])->name('document-access.request');
    Route::post('/acces-documents/{documentAccess}/decision', [DocumentController::class, 'decide'])->name('document-access.decide');
    Route::post('/acces-documents/{documentAccess}/revocation', [DocumentController::class, 'revoke'])->name('document-access.revoke');
    Route::post('/besoins/{need}/cloture', [NeedController::class, 'close'])->name('needs.close');
    Route::post('/besoins/{need}/aide', [NeedController::class, 'offerHelp'])->name('help-offers.store');
    Route::post('/aides/{helpOffer}/reponse', [NeedController::class, 'respondToOffer'])->name('help-offers.respond');

    Route::post('/suivre/projet/{project}', [FollowController::class, 'project'])->name('follows.project');
    Route::post('/suivre/membre/{user}', [FollowController::class, 'member'])->name('follows.member');
    Route::post('/suivre/secteur/{sector:slug}', [FollowController::class, 'sector'])->name('follows.sector');

    Route::get('/demandes', [ContactRequestController::class, 'index'])->name('contact-requests.index');
    Route::get('/projets/{project}/contacter', [ContactRequestController::class, 'create'])->name('contact-requests.create');
    Route::post('/demandes', [ContactRequestController::class, 'store'])->name('contact-requests.store');
    Route::get('/demandes/{contactRequest}', [ContactRequestController::class, 'show'])->name('contact-requests.show');
    Route::post('/demandes/{contactRequest}/reponse', [ContactRequestController::class, 'respond'])->name('contact-requests.respond');
    Route::post('/demandes/{contactRequest}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}/piece-jointe', [MessageController::class, 'attachment'])->name('messages.attachment');
    Route::post('/demandes/{contactRequest}/etapes', [CollaborationController::class, 'proposeAgreement'])->name('agreements.store');
    Route::post('/etapes-convenues/{agreement}/reponse', [CollaborationController::class, 'respondAgreement'])->name('agreements.respond');
    Route::post('/etapes-convenues/{agreement}/realisee', [CollaborationController::class, 'completeAgreement'])->name('agreements.complete');
    Route::post('/demandes/{contactRequest}/rendez-vous', [CollaborationController::class, 'proposeMeeting'])->name('meetings.store');
    Route::post('/rendez-vous/{meeting}/reponse', [CollaborationController::class, 'respondMeeting'])->name('meetings.respond');
    Route::post('/rendez-vous/{meeting}/annulation', [CollaborationController::class, 'cancelMeeting'])->name('meetings.cancel');
    Route::post('/demandes/{contactRequest}/collaborations', [CollaborationController::class, 'declare'])->name('collaborations.store');
    Route::post('/collaborations/{collaboration}/reponse', [CollaborationController::class, 'respondCollaboration'])->name('collaborations.respond');
    Route::get('/projets/{project}/taches', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/projets/{project}/taches', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/taches/{projectTask}/colonne', [TaskController::class, 'move'])->name('tasks.move');
    Route::delete('/taches/{projectTask}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::post('/parcours/{journey}/commencer', [JourneyController::class, 'start'])->name('journeys.start');
    Route::delete('/parcours/{journey}/suivi', [JourneyController::class, 'leave'])->name('journeys.leave');
    Route::put('/etapes/{journeyStep}', [JourneyController::class, 'updateStep'])->name('journey-steps.update');

    Route::post('/signalements', [TrustController::class, 'report'])->name('reports.store');
    Route::get('/moderation/{report}', [TrustController::class, 'decision'])->name('moderation.decision');
    Route::post('/moderation/{report}/recours', [TrustController::class, 'appeal'])->name('moderation.appeal');
    Route::post('/profil/verification', [TrustController::class, 'verify'])->name('verification.store');

    Route::put('/opportunites/{opportunity}/candidature', [OpportunityController::class, 'track'])->name('opportunities.track');
    Route::delete('/opportunites/{opportunity}/candidature', [OpportunityController::class, 'forget'])->name('opportunities.forget');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/alertes', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alertes', [AlertController::class, 'store'])->name('alerts.store');
    Route::get('/alertes/{savedSearch}', [AlertController::class, 'show'])->name('alerts.show');
    Route::delete('/alertes/{savedSearch}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    Route::post('/notifications/abonnement', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/notifications/abonnement', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
});

// Administration de l'équipe IDEE (AD-01 à AD-04).
Route::middleware(['auth', 'can:administrer'])->prefix('administration')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/signalements', [AdminController::class, 'reports'])->name('reports');
    Route::get('/signalements/{report}', [AdminController::class, 'report'])->name('reports.show');
    Route::post('/signalements/{report}/decision', [AdminController::class, 'decide'])->name('reports.decide');
    Route::post('/signalements/{report}/recours', [AdminController::class, 'reviewAppeal'])->name('reports.appeal');
    Route::get('/verifications', [AdminController::class, 'verifications'])->name('verifications');
    Route::get('/verifications/{verification}/piece', [AdminController::class, 'verificationDocument'])->name('verifications.document');
    Route::post('/verifications/{verification}/decision', [AdminController::class, 'decideVerification'])->name('verifications.decide');
    Route::get('/opportunites', [AdminController::class, 'opportunities'])->name('opportunities');
    Route::get('/opportunites/nouvelle', [AdminController::class, 'createOpportunity'])->name('opportunities.create');
    Route::post('/opportunites', [AdminController::class, 'storeOpportunity'])->name('opportunities.store');
    Route::get('/opportunites/{opportunity}', [AdminController::class, 'editOpportunity'])->name('opportunities.edit');
    Route::put('/opportunites/{opportunity}', [AdminController::class, 'updateOpportunity'])->name('opportunities.update');
    Route::delete('/opportunites/{opportunity}', [AdminController::class, 'destroyOpportunity'])->name('opportunities.destroy');
    Route::get('/fiches', [AdminController::class, 'guides'])->name('guides');
    Route::get('/fiches/{journeyStep}', [AdminController::class, 'editGuide'])->name('guides.edit');
    Route::put('/fiches/{journeyStep}', [AdminController::class, 'updateGuide'])->name('guides.update');
});

// Pages publiques : à déclarer après « /projets/nouveau » pour ne pas l'intercepter.
Route::get('/projets/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/membres/{user}', [MemberController::class, 'show'])->name('members.show');

// Confirmation d'une adresse e-mail ajoutée au profil (CF-01).
Route::get('/email/confirmation/{user}/{hash}', [ProfileController::class, 'confirmEmail'])->middleware('signed')->name('email.verify');

// Parcours guidés, consultables sans compte (PG-01 à PG-06).
Route::get('/parcours', [JourneyController::class, 'index'])->name('journeys.index');
Route::get('/parcours/forme-juridique', [JourneyController::class, 'legalForm'])->name('journeys.legal-form');
Route::get('/parcours/{journey}', [JourneyController::class, 'show'])->name('journeys.show');

// Répertoire des programmes, concours et financements (OP-01).
Route::get('/opportunites', [OpportunityController::class, 'index'])->name('opportunities.index');
Route::get('/opportunites/{opportunity}', [OpportunityController::class, 'show'])->name('opportunities.show');
