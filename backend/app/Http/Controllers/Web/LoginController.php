<?php

namespace App\Http\Controllers\Web;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestCodeRequest;
use App\Http\Requests\StartSmsPurchaseRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\SmsPurchase;
use App\Services\Auth\OneTimeCodeService;
use App\Services\Auth\SmsPurchaseService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly OneTimeCodeService $codes) {}

    public function create(SmsPurchaseService $smsPurchases): View
    {
        return view('auth.phone', [
            'countries' => Countries::options(),
            'paidSms' => $smsPurchases->isAvailable(),
            'smsPrice' => $smsPurchases->price(),
        ]);
    }

    public function sendCode(RequestCodeRequest $request): RedirectResponse
    {
        // CP-07 : le SMS se paie d'abord ; WhatsApp et l'e-mail partent tout de suite.
        if ($request->validated('channel') === 'sms' && $request->validated('phone') && ! $request->validated('sms_purchase_id')) {
            $request->session()->put('login_phone', $request->identifier());

            return redirect()->route('login.sms');
        }

        $this->codes->issue($request->identifier(), $request->validated('channel'), $request->validated('sms_purchase_id'));
        $request->session()->put('login_phone', $request->identifier());

        return redirect()->route('login.code')
            ->with('status', 'Un code vous a été envoyé. Il est valable '.config('idee.otp.ttl_minutes').' minutes.');
    }

    public function codeForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('login_phone');

        if ($phone === null) {
            return redirect()->route('login');
        }

        return view('auth.code', ['phone' => $phone, 'smsPurchase' => $request->session()->get('login_sms_purchase')]);
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $user = $this->codes->verify(
            $request->identifier(),
            $request->validated('code'),
            $request->validated('name'),
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(['login_phone', 'login_sms_purchase']);

        return redirect()->intended(route('home'))->with('status', "Bienvenue sur IDEE, {$user->name} !");
    }

    /**
     * Choix du moyen de paiement du SMS (CP-07).
     */
    public function smsForm(Request $request, SmsPurchaseService $smsPurchases): View|RedirectResponse
    {
        $phone = $request->session()->get('login_phone');

        if ($phone === null || str_contains($phone, '@')) {
            return redirect()->route('login');
        }

        return view('auth.sms', [
            'phone' => $phone,
            'price' => $smsPurchases->price(),
            'methods' => $smsPurchases->methods(),
        ]);
    }

    public function smsStart(StartSmsPurchaseRequest $request, SmsPurchaseService $smsPurchases): RedirectResponse
    {
        $purchase = $smsPurchases->start($request->validated('phone'), PaymentMethod::from($request->validated('method')));
        $request->session()->put('login_phone', $purchase->phone);

        return $purchase->launch_url
            ? redirect()->away($purchase->launch_url)
            : redirect()->route('login.sms.show', $purchase);
    }

    /**
     * Retour du paiement : dès qu'il est confirmé, le SMS part.
     */
    public function smsShow(Request $request, SmsPurchase $smsPurchase, SmsPurchaseService $smsPurchases): View|RedirectResponse
    {
        $this->ensureSamePhone($request, $smsPurchase);
        $smsPurchase = $smsPurchases->refresh($smsPurchase);

        if ($smsPurchase->isUsable() && $smsPurchase->sends_remaining === config('idee.otp.sms_sends_per_purchase')) {
            return $this->sendSms($request, $smsPurchase);
        }

        return view('auth.sms-status', ['purchase' => $smsPurchase]);
    }

    public function smsResend(Request $request, SmsPurchase $smsPurchase): RedirectResponse
    {
        $this->ensureSamePhone($request, $smsPurchase);

        return $this->sendSms($request, $smsPurchase);
    }

    private function sendSms(Request $request, SmsPurchase $purchase): RedirectResponse
    {
        $this->codes->issue($purchase->phone, 'sms', $purchase->id);
        $request->session()->put('login_phone', $purchase->phone);
        $request->session()->put('login_sms_purchase', $purchase->id);

        return redirect()->route('login.code')
            ->with('status', 'Paiement reçu, merci. Votre code part par SMS ; il est valable '.config('idee.otp.ttl_minutes').' minutes.');
    }

    /**
     * Un achat ne sert qu'au numéro saisi dans ce navigateur.
     */
    private function ensureSamePhone(Request $request, SmsPurchase $purchase): void
    {
        abort_unless($request->session()->get('login_phone') === $purchase->phone, 404);
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
