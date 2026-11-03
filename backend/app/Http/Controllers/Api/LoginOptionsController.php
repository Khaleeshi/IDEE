<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartSmsPurchaseRequest;
use App\Models\SmsPurchase;
use App\Services\Auth\SmsPurchaseService;
use App\Support\Countries;
use Illuminate\Http\JsonResponse;

/**
 * Comment recevoir son code (CP-07) : WhatsApp et e-mail gratuits, SMS payé d'avance.
 */
class LoginOptionsController extends Controller
{
    public function __construct(private readonly SmsPurchaseService $smsPurchases) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'channels' => [
                ['value' => 'whatsapp', 'label' => 'WhatsApp', 'price_fcfa' => 0, 'available' => true],
                ['value' => 'email', 'label' => 'E-mail', 'price_fcfa' => 0, 'available' => true],
                ['value' => 'sms', 'label' => 'SMS', 'price_fcfa' => $this->smsPurchases->price(), 'available' => $this->smsPurchases->isAvailable()],
            ],
            'payment_methods' => array_map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()], $this->smsPurchases->methods()),
            'countries' => Countries::all(),
        ]]);
    }

    public function store(StartSmsPurchaseRequest $request): JsonResponse
    {
        $purchase = $this->smsPurchases->start($request->validated('phone'), PaymentMethod::from($request->validated('method')));

        return response()->json(['data' => $this->present($purchase)], 201);
    }

    public function show(SmsPurchase $smsPurchase): JsonResponse
    {
        return response()->json(['data' => $this->present($this->smsPurchases->refresh($smsPurchase))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SmsPurchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'status' => ['value' => $purchase->status->value, 'label' => $purchase->status->label()],
            'amount_fcfa' => $purchase->amount_fcfa,
            'method' => $purchase->method->value,
            'payment_url' => $purchase->launch_url,
            'instructions' => $purchase->instructions,
            'sends_remaining' => $purchase->isPaid() ? $purchase->sends_remaining : null,
            'receipt_number' => $purchase->receipt_number,
        ];
    }
}
