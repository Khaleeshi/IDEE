<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebProfileRequest;
use App\Models\Sector;
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
        ]);
    }

    public function update(WebProfileRequest $request, ProfileService $profiles): RedirectResponse
    {
        $profiles->update($request->user(), $request->validated());

        return back()->with('status', 'Profil enregistré.');
    }
}
