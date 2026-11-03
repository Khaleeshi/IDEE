<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccompanimentKind;
use App\Enums\AccompanimentStatus;
use App\Http\Controllers\Controller;
use App\Models\Accompaniment;
use App\Models\MemberProfile;
use App\Models\Project;
use App\Models\Review;
use App\Services\Accompaniments\AccompanimentService;
use App\Services\Accompaniments\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Accompagnements bénévoles et de mentorat (AC-01 à AC-04), avis (AV-01 à AV-03).
 */
class AccompanimentController extends Controller
{
    public function __construct(
        private readonly AccompanimentService $accompaniments,
        private readonly ReviewService $reviews,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('accompaniments.index', [
            'asCoach' => Accompaniment::where('coach_id', $user->id)->with('requester', 'memberProfile.profession')->latest()->get(),
            'asRequester' => Accompaniment::where('requester_id', $user->id)->with('coach', 'memberProfile.profession')->latest()->get(),
        ]);
    }

    public function create(Request $request, MemberProfile $memberProfile): View
    {
        abort_unless($memberProfile->isPublic() && ($memberProfile->offers_mentoring || $memberProfile->offers_volunteer), 404);

        return view('accompaniments.create', [
            'profile' => $memberProfile->load('user', 'profession'),
            'projects' => $request->user()->projects()->pluck('title', 'id'),
        ]);
    }

    public function store(Request $request, MemberProfile $memberProfile): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(AccompanimentKind::class)],
            'objectives' => ['required', 'string', 'max:3000'],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
        ], ['objectives.required' => 'Décrivez ce que vous attendez de cet accompagnement.']);

        $accompaniment = $this->accompaniments->request(
            $request->user(),
            $memberProfile,
            AccompanimentKind::from($data['kind']),
            $data['objectives'],
            isset($data['project_id']) ? Project::find($data['project_id']) : null,
        );

        return redirect()->route('accompaniments.show', $accompaniment)->with('status', 'Demande envoyée. Vous serez prévenu de la réponse.');
    }

    public function show(Accompaniment $accompaniment): View
    {
        Gate::authorize('view', $accompaniment);

        return view('accompaniments.show', [
            'accompaniment' => $accompaniment->load('requester', 'coach', 'memberProfile.profession', 'project', 'sessions.author', 'review'),
        ]);
    }

    public function respond(Request $request, Accompaniment $accompaniment): RedirectResponse
    {
        Gate::authorize('respond', $accompaniment);
        $data = $request->validate([
            'accepted' => ['required', 'boolean'],
            'reason' => ['required_if:accepted,0', 'nullable', 'string', 'max:1000'],
        ], ['reason.required_if' => 'Indiquez en quelques mots pourquoi vous ne pouvez pas.']);

        $request->boolean('accepted')
            ? $this->accompaniments->accept($accompaniment)
            : $this->accompaniments->decline($accompaniment, $data['reason']);

        return back()->with('status', $request->boolean('accepted') ? 'Accompagnement accepté.' : 'Réponse envoyée.');
    }

    public function cancel(Accompaniment $accompaniment): RedirectResponse
    {
        Gate::authorize('cancel', $accompaniment);
        $this->accompaniments->cancel($accompaniment);

        return back()->with('status', 'Demande retirée.');
    }

    public function storeSession(Request $request, Accompaniment $accompaniment): RedirectResponse
    {
        Gate::authorize('view', $accompaniment);
        $data = $request->validate([
            'held_on' => ['required', 'date'],
            'summary' => ['required', 'string', 'max:3000'],
            'next_steps' => ['nullable', 'string', 'max:2000'],
        ], ['summary.required' => 'Résumez ce qui s\'est dit.', 'held_on.required' => 'Indiquez la date de la séance.']);

        $this->accompaniments->addSession($accompaniment, $request->user(), Carbon::parse($data['held_on']), $data['summary'], $data['next_steps'] ?? null);

        return back()->with('status', 'Point de suivi enregistré.');
    }

    public function complete(Request $request, Accompaniment $accompaniment): RedirectResponse
    {
        Gate::authorize('view', $accompaniment);
        $this->accompaniments->complete($accompaniment, $request->user());

        return back()->with('status', 'Accompagnement terminé.');
    }

    public function review(Request $request, Accompaniment $accompaniment): RedirectResponse
    {
        Gate::authorize('cancel', $accompaniment);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], ['rating.required' => 'Choisissez une note de 1 à 5.']);

        $this->reviews->forAccompaniment($accompaniment, $request->user(), (int) $data['rating'], $data['comment'] ?? null);

        return back()->with('status', 'Merci pour votre avis.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['reply' => ['required', 'string', 'max:1000']], ['reply.required' => 'Écrivez votre réponse.']);
        $this->reviews->reply($review, $request->user(), $data['reply']);

        return back()->with('status', 'Réponse publiée.');
    }

    /**
     * Couleur de l'étiquette d'état.
     */
    public static function statusClasses(AccompanimentStatus $status): string
    {
        return match ($status) {
            AccompanimentStatus::EnCours => 'bg-brand-50 text-brand-800',
            AccompanimentStatus::Demandee => 'bg-amber-100 text-amber-900',
            default => 'bg-stone-100 text-stone-600',
        };
    }
}
