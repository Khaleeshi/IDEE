<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnumResource;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Experts\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Demandes de devis aux experts et suivi des prestations (SE-02).
 */
class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => QuoteRequest::query()
            ->where(fn ($query) => $query->where('client_id', $user->id)->orWhere('expert_id', $user->id))
            ->with(['client', 'expert'])
            ->latest()
            ->get()
            ->map($this->present(...))]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:30', 'max:3000'],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
            'budget' => ['nullable', 'string', 'max:120'],
            'wanted_by' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $quote = $this->quotes->request(
            $request->user(),
            $user,
            $data['description'],
            isset($data['project_id']) ? Project::find($data['project_id']) : null,
            $data['budget'] ?? null,
            isset($data['wanted_by']) ? Carbon::parse($data['wanted_by']) : null,
        );

        return response()->json(['data' => $this->present($quote->load(['client', 'expert']))], Response::HTTP_CREATED);
    }

    public function show(QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('view', $quoteRequest);

        return response()->json(['data' => $this->present($quoteRequest->load(['client', 'expert']))]);
    }

    public function sendQuote(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('quote', $quoteRequest);
        $data = $request->validate([
            'quote_amount_fcfa' => ['required', 'integer', 'min:0'],
            'quote_delay' => ['required', 'string', 'max:120'],
            'quote_details' => ['required', 'string', 'max:3000'],
        ]);

        return response()->json(['data' => $this->present($this->quotes->sendQuote($quoteRequest, (int) $data['quote_amount_fcfa'], $data['quote_delay'], $data['quote_details']))]);
    }

    public function answer(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('answer', $quoteRequest);
        $request->validate(['accepted' => ['required', 'boolean']]);

        return response()->json(['data' => $this->present($this->quotes->answer($quoteRequest, $request->boolean('accepted')))]);
    }

    public function deliver(QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('quote', $quoteRequest);

        return response()->json(['data' => $this->present($this->quotes->deliver($quoteRequest))]);
    }

    public function complete(QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('answer', $quoteRequest);

        return response()->json(['data' => $this->present($this->quotes->complete($quoteRequest))]);
    }

    public function cancel(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        Gate::authorize('view', $quoteRequest);

        return response()->json(['data' => $this->present($this->quotes->cancel($quoteRequest, $request->user()))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(QuoteRequest $quote): array
    {
        return [
            'id' => $quote->id,
            'client' => ['id' => $quote->client_id, 'name' => $quote->client?->name],
            'expert' => ['id' => $quote->expert_id, 'name' => $quote->expert?->name],
            'project_id' => $quote->project_id,
            'description' => $quote->description,
            'budget' => $quote->budget,
            'wanted_by' => $quote->wanted_by?->toDateString(),
            'status' => EnumResource::make($quote->status),
            'quote' => $quote->quoted_at ? [
                'amount_fcfa' => $quote->quote_amount_fcfa,
                'delay' => $quote->quote_delay,
                'details' => $quote->quote_details,
                'sent_at' => $quote->quoted_at->toIso8601String(),
            ] : null,
            'created_at' => $quote->created_at?->toIso8601String(),
        ];
    }
}
