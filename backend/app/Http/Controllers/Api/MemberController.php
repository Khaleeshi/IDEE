<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Http\Resources\ProjectResource;
use App\Models\User;
use App\Services\ContactRequests\ReactivityIndicator;

class MemberController extends Controller
{
    /**
     * Profil public, avec l'indicateur de réactivité (MR-06).
     */
    public function show(User $user, ReactivityIndicator $reactivity): MemberResource
    {
        $projects = $user->projects()->active()->with(['sector', 'openNeeds'])->latest('last_activity_at')->get();

        return (new MemberResource($user->load('roleAssignments')))->additional([
            'reactivity' => $reactivity->for($user),
            'projects' => ProjectResource::collection($projects),
        ]);
    }
}
