<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource($request->user()->load('roleAssignments'));
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $user = $request->user();
        $user->fill(Arr::except($request->validated(), ['roles']));
        $user->save();

        if ($request->has('roles')) {
            $user->syncRoles(array_map(UserRole::from(...), $request->validated('roles')));
        }

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
