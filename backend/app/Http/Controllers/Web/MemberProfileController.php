<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileRequest;
use App\Models\MemberProfile;
use App\Models\Profession;
use App\Services\Members\MemberProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Mes métiers : profils professionnels du membre (PM-02 à PM-05).
 */
class MemberProfileController extends Controller
{
    public function __construct(private readonly MemberProfileService $profiles) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('profile.professions', [
            'user' => $user,
            'profiles' => $user->memberProfiles()->with('profession')->get(),
            'professions' => Profession::grouped(),
            'max' => config('idee.member_profiles.max_per_member'),
        ]);
    }

    public function store(MemberProfileRequest $request): RedirectResponse
    {
        $this->profiles->create($request->user(), $request->validated());

        return redirect()->route('member-profiles.index')->with('status', 'Profil ajouté.');
    }

    public function edit(MemberProfile $memberProfile): View
    {
        Gate::authorize('update', $memberProfile);

        return view('profile.profession', ['profile' => $memberProfile->load('profession', 'user')]);
    }

    public function update(MemberProfileRequest $request, MemberProfile $memberProfile): RedirectResponse
    {
        Gate::authorize('update', $memberProfile);
        $this->profiles->update($memberProfile, $request->validated());

        return redirect()->route('member-profiles.index')->with('status', 'Profil enregistré.');
    }

    public function destroy(MemberProfile $memberProfile): RedirectResponse
    {
        Gate::authorize('delete', $memberProfile);
        $this->profiles->delete($memberProfile);

        return redirect()->route('member-profiles.index')->with('status', 'Profil supprimé. Votre compte et vos échanges ne changent pas.');
    }
}
