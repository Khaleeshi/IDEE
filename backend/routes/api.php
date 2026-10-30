<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\HelpOfferController;
use App\Http\Controllers\Api\JourneyController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NeedController;
use App\Http\Controllers\Api\NetworkController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUpdateController;
use App\Http\Controllers\Api\SectorController;
use Illuminate\Support\Facades\Route;

// Connexion par code à usage unique.
Route::prefix('auth')->group(function () {
    Route::post('code', [AuthController::class, 'requestCode'])->middleware('throttle:otp');
    Route::post('verify', [AuthController::class, 'verifyCode'])->middleware('throttle:otp-verify');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Consultation publique.
Route::get('sectors', [SectorController::class, 'index']);
Route::get('projects', [ProjectController::class, 'index']);
Route::get('projects/{project}', [ProjectController::class, 'show']);
Route::get('projects/{project}/needs', [NeedController::class, 'index']);
Route::get('projects/{project}/updates', [ProjectUpdateController::class, 'index']);
Route::get('feed', [FeedController::class, 'index']);
Route::get('members/{user}', [MemberController::class, 'show']);
Route::get('journeys', [JourneyController::class, 'index']);
Route::get('journeys/{journey}', [JourneyController::class, 'show']);
Route::get('legal-form', [JourneyController::class, 'legalForm']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [ProfileController::class, 'show']);
    Route::patch('me', [ProfileController::class, 'update']);
    Route::get('me/projects', [ProfileController::class, 'projects']);
    Route::get('me/journeys', [JourneyController::class, 'mine']);
    Route::get('me/feed', [NetworkController::class, 'followedFeed']);
    Route::get('me/recommendations', [NetworkController::class, 'recommendations']);
    Route::post('follows/projects/{project}', [NetworkController::class, 'followProject']);
    Route::post('follows/members/{user}', [NetworkController::class, 'followMember']);
    Route::post('follows/sectors/{sector:slug}', [NetworkController::class, 'followSector']);

    Route::post('journeys/{journey}/start', [JourneyController::class, 'start']);
    Route::delete('journeys/{journey}/start', [JourneyController::class, 'leave']);
    Route::patch('journey-steps/{journeyStep}/progress', [JourneyController::class, 'updateStep']);

    Route::post('projects', [ProjectController::class, 'store']);
    Route::patch('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

    Route::post('projects/{project}/updates', [ProjectUpdateController::class, 'store']);
    Route::post('projects/{project}/needs', [NeedController::class, 'store']);
    Route::post('needs/{need}/close', [NeedController::class, 'close']);

    Route::get('needs/{need}/help-offers', [HelpOfferController::class, 'index']);
    Route::post('needs/{need}/help-offers', [HelpOfferController::class, 'store']);
    Route::post('help-offers/{helpOffer}/respond', [HelpOfferController::class, 'respond']);

    Route::get('contact-requests', [ContactRequestController::class, 'index']);
    Route::post('contact-requests', [ContactRequestController::class, 'store']);
    Route::get('contact-requests/{contactRequest}', [ContactRequestController::class, 'show']);
    Route::post('contact-requests/{contactRequest}/respond', [ContactRequestController::class, 'respond']);
    Route::get('contact-requests/{contactRequest}/messages', [MessageController::class, 'index']);
    Route::post('contact-requests/{contactRequest}/messages', [MessageController::class, 'store']);
    Route::get('messages/{message}/attachment', [MessageController::class, 'attachment']);
});
