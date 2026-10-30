<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Members\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Acceptation des conditions d'utilisation et de la charte communautaire (section 8).
 */
class TermsController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasAcceptedTerms()) {
            return redirect()->route('home');
        }

        return view('legal.accept', ['renewal' => $request->user()->terms_version !== null]);
    }

    public function accept(Request $request, ProfileService $profiles): RedirectResponse
    {
        $request->validate(
            ['accept' => ['accepted']],
            ['accept.accepted' => 'Cochez la case pour accepter les conditions d\'utilisation et la charte.'],
        );

        $profiles->acceptTerms($request->user());

        return redirect()->intended(route('home'));
    }
}
