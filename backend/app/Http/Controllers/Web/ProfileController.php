<?php

namespace App\Http\Controllers\Web;

use App\Enums\Availability;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebProfileRequest;
use App\Models\Sector;
use App\Models\User;
use App\Services\Members\ProfileService;
use App\Services\Moderation\ModerationService;
use App\Services\Trust\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, VerificationService $verifications, ModerationService $moderation): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('roleAssignments'),
            'verification' => $verifications->latestFor($request->user()),
            'decisions' => $moderation->decisionsAbout($request->user()),
            'sectors' => Sector::options('slug'),
            'roles' => UserRole::cases(),
            'availabilities' => collect(Availability::cases())->mapWithKeys(fn (Availability $availability) => [$availability->value => $availability->label()]),
        ]);
    }

    public function update(WebProfileRequest $request, ProfileService $profiles): RedirectResponse
    {
        $profiles->update($request->user(), $request->validated());

        return back()->with('status', 'Profil enregistré.');
    }

    /**
     * Lien de confirmation d'adresse e-mail (lien signé, valable 24 heures).
     */
    public function confirmEmail(User $user, string $hash, ProfileService $profiles): RedirectResponse
    {
        $confirmed = $profiles->confirmEmail($user, $hash);

        return redirect()->route(auth()->check() ? 'profile.edit' : 'login')->with('status', $confirmed
            ? 'Adresse e-mail confirmée : vous pouvez vous connecter avec elle.'
            : 'Ce lien ne correspond plus à votre adresse. Demandez-en un nouveau depuis votre profil.');
    }
}
