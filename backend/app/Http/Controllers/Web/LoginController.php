<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Services\Auth\OneTimeCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly OneTimeCodeService $codes) {}

    public function create(): View
    {
        return view('auth.phone');
    }

    public function sendCode(RequestCodeRequest $request): RedirectResponse
    {
        $this->codes->issue($request->validated('phone'));
        $request->session()->put('login_phone', $request->validated('phone'));

        return redirect()->route('login.code')
            ->with('status', 'Un code vous a été envoyé. Il est valable '.config('idee.otp.ttl_minutes').' minutes.');
    }

    public function codeForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('login_phone');

        if ($phone === null) {
            return redirect()->route('login');
        }

        return view('auth.code', ['phone' => $phone]);
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $user = $this->codes->verify(
            $request->validated('phone'),
            $request->validated('code'),
            $request->validated('name'),
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget('login_phone');

        return redirect()->intended(route('home'))->with('status', "Bienvenue sur IDEE, {$user->name} !");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Vide aussi les pages gardées hors connexion : le téléphone peut être partagé.
        return redirect()->route('home')
            ->with('status', 'Vous êtes déconnecté.')
            ->with('clear_offline_cache', true);
    }
}
