<?php

namespace App\Http\Controllers\Web;

use App\Enums\Offer;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Offres payantes, paiement par Wave ou Orange Money, reçus (PA-01 à PA-03).
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function offers(Request $request): View
    {
        $user = $request->user();

        return view('payments.offers', [
            'offers' => Offer::memberOffers(),
            'enabled' => $this->payments->isEnabled(),
            'active' => $user ? $user->entitlements()->active()->with('project')->orderBy('ends_at')->get() : collect(),
            'history' => $user ? Payment::whereBelongsTo($user)->with('project')->latest()->limit(20)->get() : collect(),
        ]);
    }

    public function checkout(Request $request, Offer $offer): View|RedirectResponse
    {
        $projects = $request->user()->projects()->active()->latest('last_activity_at')->get();

        if ($offer->isForProject() && $projects->isEmpty()) {
            return redirect()->route('offers.index')->with('status', 'Publiez d\'abord un projet pour pouvoir le mettre en avant.');
        }

        return view('payments.checkout', [
            'offer' => $offer,
            'methods' => $this->payments->methods(),
            'projects' => $projects,
            'project' => $projects->firstWhere('id', $request->query('projet')) ?? $projects->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'offer' => ['required', Rule::enum(Offer::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:52'],
        ], ['method.required' => 'Choisissez un moyen de paiement.']);

        $payment = $this->payments->start(
            $request->user(),
            Offer::from($data['offer']),
            PaymentMethod::from($data['method']),
            isset($data['project_id']) ? Project::find($data['project_id']) : null,
            (int) ($data['quantity'] ?? 1),
        );

        return $payment->launch_url
            ? redirect()->away($payment->launch_url)
            : redirect()->route('payments.show', $payment);
    }

    public function show(Request $request, Payment $payment): View
    {
        $this->authorizeBuyer($request, $payment);

        return view('payments.show', ['payment' => $payment->load('project')]);
    }

    /**
     * Retour depuis Wave, payé ou non : le statut est relu auprès de Wave.
     */
    public function returnFromWave(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeBuyer($request, $payment);
        $payment = $this->payments->refresh($payment);

        return redirect()->route('payments.show', $payment)->with('status', match ($payment->status) {
            PaymentStatus::Payee => 'Paiement reçu, merci ! Votre offre est active.',
            PaymentStatus::Echouee => 'Le paiement n\'a pas abouti. Vous pouvez réessayer.',
            default => 'Paiement en cours de confirmation par Wave. Actualisez dans un instant.',
        });
    }

    public function check(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeBuyer($request, $payment);
        $this->payments->refresh($payment);

        return redirect()->route('payments.show', $payment);
    }

    public function reference(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeBuyer($request, $payment);
        $data = $request->validate(['payer_reference' => ['required', 'string', 'max:100']], [
            'payer_reference.required' => 'Indiquez l\'identifiant du transfert reçu par SMS d\'Orange Money.',
        ]);

        $this->payments->submitReference($payment, $data['payer_reference']);

        return redirect()->route('payments.show', $payment)->with('status', 'Merci ! L\'équipe vérifie votre transfert et active votre offre.');
    }

    public function receipt(Request $request, Payment $payment): View
    {
        $this->authorizeBuyer($request, $payment);
        abort_unless($payment->isPaid(), 404);

        return view('payments.receipt', ['payment' => $payment->load(['user', 'project'])]);
    }

    /**
     * Facture d'une organisation, pour ceux qui la gèrent et pour l'équipe.
     */
    public function invoice(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user()->isAdmin() || $invoice->organisation->isManagedBy($request->user()), 404);

        return view('payments.invoice', ['invoice' => $invoice->load('organisation')]);
    }

    private function authorizeBuyer(Request $request, Payment $payment): void
    {
        abort_unless($payment->user_id === $request->user()->id || $request->user()->isAdmin(), 404);
    }
}
