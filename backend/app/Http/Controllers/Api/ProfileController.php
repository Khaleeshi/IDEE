<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProjectResource;
use App\Services\Members\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
