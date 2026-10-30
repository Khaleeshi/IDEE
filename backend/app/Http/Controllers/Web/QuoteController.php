<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Experts\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Demandes de devis aux experts et suivi des prestations (SE-02).
 */
class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('quotes.index', [
            'asClient' => QuoteRequest::where('client_id', $user->id)->with(['expert', 'project'])->latest()->get(),
            'asExpert' => QuoteRequest::where('expert_id', $user->id)->with(['client', 'project'])->latest()->get(),
        ]);
    }

    public function create(Request $request, User $user): View
    {
        return view('quotes.create', ['expert' => $user, 'projects' => $request->user()->projects()->active()->get()]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:30', 'max:3000'],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
            'budget' => ['nullable', 'string', 'max:120'],
            'wanted_by' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'description.required' => 'Décrivez la prestation attendue.',
            'description.min' => 'Décrivez la prestation en quelques phrases : ce qu\'il faut faire, pour quand, avec quels documents.',
        ]);

        $quote = $this->quotes->request(
            $request->user(),
            $user,
            $data['description'],
            isset($data['project_id']) ? Project::find($data['project_id']) : null,
            $data['budget'] ?? null,
            isset($data['wanted_by']) ? Carbon::parse($data['wanted_by']) : null,
        );

        return redirect()->route('quotes.show', $quote)->with('status', 'Demande envoyée à '.$user->name.'.');
    }

    public function show(QuoteRequest $quoteRequest): View
    {
        Gate::authorize('view', $quoteRequest);

        return view('quotes.show', ['quote' => $quoteRequest->load(['client', 'expert', 'project'])]);
    }

    public function sendQuote(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        Gate::authorize('quote', $quoteRequest);
        $data = $request->validate([
            'quote_amount_fcfa' => ['required', 'integer', 'min:0'],
            'quote_delay' => ['required', 'string', 'max:120'],
            'quote_details' => ['required', 'string', 'max:3000'],
        ], [
            'quote_amount_fcfa.required' => 'Indiquez le montant du devis.',
            'quote_delay.required' => 'Indiquez le délai, par exemple « 10 jours ».',
            'quote_details.required' => 'Détaillez ce que comprend la prestation.',
        ]);

        $this->quotes->sendQuote($quoteRequest, (int) $data['quote_amount_fcfa'], $data['quote_delay'], $data['quote_details']);

        return redirect()->route('quotes.show', $quoteRequest)->with('status', 'Devis envoyé.');
    }

    public function answer(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        Gate::authorize('answer', $quoteRequest);
        $request->validate(['accepted' => ['required', 'boolean']]);

        $this->quotes->answer($quoteRequest, $request->boolean('accepted'));

        return redirect()->route('quotes.show', $quoteRequest)->with('status', $request->boolean('accepted')
            ? 'Devis accepté. Réglez la prestation directement avec l\'expert, selon ce que vous convenez.'
            : 'Devis refusé.');
    }

    public function deliver(QuoteRequest $quoteRequest): RedirectResponse
    {
        Gate::authorize('quote', $quoteRequest);

        $this->quotes->deliver($quoteRequest);

        return redirect()->route('quotes.show', $quoteRequest)->with('status', 'Livraison signalée au client.');
    }

    public function complete(QuoteRequest $quoteRequest): RedirectResponse
    {
        Gate::authorize('answer', $quoteRequest);

        $this->quotes->complete($quoteRequest);

        return redirect()->route('quotes.show', $quoteRequest)->with('status', 'Prestation terminée.');
    }

    public function cancel(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        Gate::authorize('view', $quoteRequest);

        $this->quotes->cancel($quoteRequest, $request->user());

        return redirect()->route('quotes.show', $quoteRequest)->with('status', 'Demande annulée.');
    }
}
