<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\HelpOfferController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NeedController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
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
Route::get('members/{user}', [MemberController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [ProfileController::class, 'show']);
    Route::patch('me', [ProfileController::class, 'update']);
    Route::get('me/projects', [ProfileController::class, 'projects']);

    Route::post('projects', [ProjectController::class, 'store']);
    Route::patch('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

    Route::post('projects/{project}/needs', [NeedController::class, 'store']);
    Route::post('needs/{need}/close', [NeedController::class, 'close']);

    Route::get('needs/{need}/help-offers', [HelpOfferController::class, 'index']);
    Route::post('needs/{need}/help-offers', [HelpOfferController::class, 'store']);
    Route::post('help-offers/{helpOffer}/respond', [HelpOfferController::class, 'respond']);

    Route::get('contact-requests', [ContactRequestController::class, 'index']);
    Route::post('contact-requests', [ContactRequestController::class, 'store']);
    Route::get('contact-requests/{contactRequest}', [ContactRequestController::class, 'show']);
    Route::post('contact-requests/{contactRequest}/respond', [ContactRequestController::class, 'respond']);
});
