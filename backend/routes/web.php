<?php

use App\Http\Controllers\Web\ContactRequestController;
use App\Http\Controllers\Web\FeedController;
use App\Http\Controllers\Web\FollowController;
use App\Http\Controllers\Web\JourneyController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MemberController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\NeedController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ProjectController;
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
    Route::post('/projets/{project}/besoins', [NeedController::class, 'store'])->name('needs.store');
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

    Route::post('/parcours/{journey}/commencer', [JourneyController::class, 'start'])->name('journeys.start');
    Route::delete('/parcours/{journey}/suivi', [JourneyController::class, 'leave'])->name('journeys.leave');
    Route::put('/etapes/{journeyStep}', [JourneyController::class, 'updateStep'])->name('journey-steps.update');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
});

// Pages publiques : à déclarer après « /projets/nouveau » pour ne pas l'intercepter.
Route::get('/projets/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/membres/{user}', [MemberController::class, 'show'])->name('members.show');

// Parcours guidés, consultables sans compte (PG-01 à PG-06).
Route::get('/parcours', [JourneyController::class, 'index'])->name('journeys.index');
Route::get('/parcours/forme-juridique', [JourneyController::class, 'legalForm'])->name('journeys.legal-form');
Route::get('/parcours/{journey}', [JourneyController::class, 'show'])->name('journeys.show');
