<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProjectResource;
use App\Services\Members\AccountService;
use App\Services\Members\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource($request->user()->load('roleAssignments'));
    }

    public function update(UpdateProfileRequest $request, ProfileService $profiles): ProfileResource
    {
        $user = $profiles->update($request->user(), $request->validated());

        return new ProfileResource($user->load('roleAssignments'));
    }

    /**
     * Acceptation des conditions d'utilisation et de la charte communautaire.
     */
    public function acceptTerms(Request $request, ProfileService $profiles): ProfileResource
    {
        $request->validate(
            ['accept' => ['accepted']],
            ['accept.accepted' => 'Acceptez les conditions d\'utilisation et la charte pour continuer.'],
        );

        return new ProfileResource($profiles->acceptTerms($request->user())->load('roleAssignments'));
    }

    /**
     * Projets du membre connecté, y compris en sommeil ou archivés.
     */
    public function projects(Request $request): AnonymousResourceCollection
    {
        $projects = $request->user()->projects()
            ->with(['sector', 'openNeeds'])
            ->latest('last_activity_at')
            ->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Toutes les données du membre connecté (section 10).
     */
    public function export(Request $request, AccountService $accounts): JsonResponse
    {
        return response()->json(['data' => $accounts->export($request->user())], options: JSON_UNESCAPED_UNICODE);
    }

    /**
     * Suppression du compte, exigée aussi par Apple pour publier l'application.
     */
    public function destroy(DeleteAccountRequest $request, AccountService $accounts): Response
    {
        $accounts->delete($request->user());

        return response()->noContent();
    }
}
