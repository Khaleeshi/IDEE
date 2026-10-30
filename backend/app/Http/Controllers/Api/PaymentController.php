<?php

namespace App\Http\Controllers\Api;

use App\Enums\Offer;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnumResource;
use App\Models\Payment;
use App\Models\Project;
use App\Services\Payments\PaymentService;
use App\Services\Projects\VisibilityStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Offres payantes et paiements (PA-01 à PA-03).
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /**
     * Offres, prix et moyens de paiement disponibles.
     */
    public function offers(): JsonResponse
    {
        return response()->json(['data' => [
            'offers' => collect(Offer::cases())->map(fn (Offer $offer) => [
                ...EnumResource::make($offer),
                'price_fcfa' => $offer->price(),
                'days' => $offer->days(),
                'benefits' => $offer->benefits(),
                'for_project' => $offer->isForProject(),
            ]),
            'methods' => array_map(EnumResource::make(...), $this->payments->methods()),
        ]]);
    }

    /**
     * Ouvre un achat. Avec Wave, `launch_url` mène à la page de paiement Wave.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'offer' => ['required', Rule::enum(Offer::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:52'],
        ]);

        $payment = $this->payments->start(
            $request->user(),
            Offer::from($data['offer']),
            PaymentMethod::from($data['method']),
            isset($data['project_id']) ? Project::find($data['project_id']) : null,
            (int) ($data['quantity'] ?? 1),
        );

        return response()->json(['data' => [...$this->present($payment), 'launch_url' => $payment->launch_url]], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => Payment::whereBelongsTo($request->user())->latest()->limit(50)->get()->map($this->present(...))]);
    }

    /**
     * Un achat ; un paiement Wave en attente est relu auprès de Wave.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        return response()->json(['data' => $this->present($this->payments->refresh($payment))]);
    }

    public function reference(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, Response::HTTP_NOT_FOUND);
        $data = $request->validate(['payer_reference' => ['required', 'string', 'max:100']]);

        return response()->json(['data' => $this->present($this->payments->submitReference($payment, $data['payer_reference']))]);
    }

    /**
     * Avis de Wave : il déclenche seulement une relecture du paiement auprès de Wave.
     */
    public function waveWebhook(Request $request): Response
    {
        $reference = $request->input('data.client_reference');
        $session = $request->input('data.id');

        $payment = Payment::query()
            ->when(is_string($reference), fn ($query) => $query->whereKey($reference), fn ($query) => $query->where('provider_reference', (string) $session))
            ->first();

        if ($payment !== null) {
            $this->payments->refresh($payment);
        }

        return response()->noContent();
    }

    /**
     * Statistiques de visibilité d'un projet, avec l'offre Pro.
     */
    public function stats(Request $request, Project $project, VisibilityStats $visibility): JsonResponse
    {
        Gate::authorize('update', $project);
        abort_unless($visibility->isAvailableTo($request->user()), Response::HTTP_PAYMENT_REQUIRED, 'Les statistiques de visibilité sont incluses dans l\'offre Pro.');

        return response()->json(['data' => $visibility->for($project)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'offer' => EnumResource::make($payment->offer),
            'project_id' => $payment->project_id,
            'quantity' => $payment->quantity,
            'amount_fcfa' => $payment->amount_fcfa,
            'method' => EnumResource::make($payment->method),
            'status' => EnumResource::make($payment->status),
            'payer_reference' => $payment->payer_reference,
            'receipt_number' => $payment->receipt_number,
            'receipt_url' => $payment->isPaid() ? route('payments.receipt', $payment) : null,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }
}
