<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ContactRequests\ReactivityIndicator;
use App\Services\Network\FollowService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function show(Request $request, User $user, ReactivityIndicator $reactivity, FollowService $follows): View
    {
        return view('members.show', [
            'member' => $user->load('roleAssignments'),
            'following' => $follows->isFollowing($request->user(), $user),
            'reactivity' => $reactivity->for($user),
            'projects' => $user->projects()->active()->with(['sector', 'openNeeds'])->latest('last_activity_at')->get(),
        ]);
    }
}
