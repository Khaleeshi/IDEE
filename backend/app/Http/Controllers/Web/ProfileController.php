<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebProfileRequest;
use App\Models\Sector;
use App\Services\Members\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('roleAssignments'),
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
