<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use App\Services\Collaboration\CollaborationService;
use App\Services\ContactRequests\ReactivityIndicator;
use App\Services\Network\FollowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function show(Request $request, User $user, ReactivityIndicator $reactivity, FollowService $follows, CollaborationService $collaborations): View
    {
        return view('members.show', [
            'member' => $user->load('roleAssignments'),
            'following' => $follows->isFollowing($request->user(), $user),
            'reactivity' => $reactivity->for($user),
            'projects' => $user->projects()->active()->with(['sector', 'openNeeds'])->latest('last_activity_at')->get(),
            // CO-03 : historique des collaborations confirmées par les deux parties.
            'collaborations' => $collaborations->confirmedFor($user),
            // CP-05 : pages d'organisation dont le membre fait partie.
            'organisations' => Organisation::whereHas('members', fn ($query) => $query->where('user_id', $user->id))->orderBy('name')->get(),
        ]);
    }

    /**
     * Demande de contact à un membre (un expert par exemple), à propos d'un de mes projets (RG-02).
     */
    public function contact(Request $request, User $user): View|RedirectResponse
    {
        $projects = $request->user()->projects()->active()->latest('last_activity_at')->get();

        if ($user->is($request->user()) || $projects->isEmpty()) {
            return redirect()->route('members.show', $user)->with('status', $projects->isEmpty()
                ? 'Publiez d\'abord votre projet : une demande de contact porte sur un projet précis.'
                : 'C\'est votre propre profil.');
        }

        $sender = $request->user();

        return view('contact-requests.member', [
            'member' => $user,
            'projects' => $projects,
            'quota' => $sender->weeklyContactQuota(),
            'used' => $sender->sentContactRequests()->where('created_at', '>=', now()->subWeek())->count(),
        ]);
    }
}
