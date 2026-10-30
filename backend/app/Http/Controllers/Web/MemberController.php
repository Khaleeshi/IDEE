<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ContactRequests\ReactivityIndicator;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function show(User $user, ReactivityIndicator $reactivity): View
    {
        return view('members.show', [
            'member' => $user->load('roleAssignments'),
            'reactivity' => $reactivity->for($user),
            'projects' => $user->projects()->active()->with(['sector', 'openNeeds'])->latest('last_activity_at')->get(),
        ]);
    }
}
