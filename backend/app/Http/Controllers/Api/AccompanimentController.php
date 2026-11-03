<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccompanimentKind;
use App\Http\Controllers\Controller;
use App\Models\Accompaniment;
use App\Models\MemberProfile;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Services\Accompaniments\AccompanimentService;
use App\Services\Accompaniments\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Accompagnements (AC-01 à AC-04) et avis (AV-01 à AV-03) dans l'API.
 */
class AccompanimentController extends Controller
{
    public function __construct(
        private readonly AccompanimentService $accompaniments,
        private readonly ReviewService $reviews,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = Accompaniment::where(fn ($query) => $query->where('coach_id', $user->id)->orWhere('requester_id', $user->id))
            ->with('requester', 'coach', 'memberProfile.profession', 'project', 'sessions', 'review')->latest()->get();

        return response()->json(['data' => $items->map(fn (Accompaniment $item) => $this->present($item, $user))]);
    }

    public function store(Request $request, MemberProfile $memberProfile): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(AccompanimentKind::class)],
            'objectives' => ['required', 'string', 'max:3000'],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
        ]);

        $item = $this->accompaniments->request($request->user(), $memberProfile, AccompanimentKind::from($data['kind']), $data['objectives'],
            isset($data['project_id']) ? Project::find($data['project_id']) : null);

        return response()->json(['data' => $this->present($item, $request->user())], 201);
    }

    public function show(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('view', $accompaniment);

        return response()->json(['data' => $this->present($accompaniment, $request->user())]);
    }

    public function respond(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('respond', $accompaniment);
        $data = $request->validate(['accepted' => ['required', 'boolean'], 'reason' => ['required_if:accepted,false', 'nullable', 'string', 'max:1000']]);
        $request->boolean('accepted') ? $this->accompaniments->accept($accompaniment) : $this->accompaniments->decline($accompaniment, $data['reason']);

        return response()->json(['data' => $this->present($accompaniment, $request->user())]);
    }

    public function cancel(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('cancel', $accompaniment);

        return response()->json(['data' => $this->present($this->accompaniments->cancel($accompaniment), $request->user())]);
    }

    public function storeSession(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('view', $accompaniment);
        $data = $request->validate(['held_on' => ['required', 'date'], 'summary' => ['required', 'string', 'max:3000'], 'next_steps' => ['nullable', 'string', 'max:2000']]);
        $this->accompaniments->addSession($accompaniment, $request->user(), Carbon::parse($data['held_on']), $data['summary'], $data['next_steps'] ?? null);

        return response()->json(['data' => $this->present($accompaniment, $request->user())], 201);
    }

    public function complete(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('view', $accompaniment);

        return response()->json(['data' => $this->present($this->accompaniments->complete($accompaniment, $request->user()), $request->user())]);
    }

    public function review(Request $request, Accompaniment $accompaniment): JsonResponse
    {
        Gate::authorize('cancel', $accompaniment);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5'], 'comment' => ['nullable', 'string', 'max:2000']]);
        $this->reviews->forAccompaniment($accompaniment, $request->user(), (int) $data['rating'], $data['comment'] ?? null);

        return response()->json(['data' => $this->present($accompaniment, $request->user())], 201);
    }

    public function reply(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate(['reply' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => self::presentReview($this->reviews->reply($review, $request->user(), $data['reply']))]);
    }

    /**
     * Avis publics reçus par un membre, avec la note moyenne.
     */
    public function memberReviews(User $user): JsonResponse
    {
        return response()->json([
            'summary' => $this->reviews->summary($user),
            'data' => Review::where('reviewee_id', $user->id)->with('reviewer')->latest()->limit(50)->get()->map(fn (Review $review) => self::presentReview($review)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function presentReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'reviewer' => $review->reviewer?->name ?? 'Membre supprimé',
            'reply' => $review->reply,
            'created_at' => $review->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Accompaniment $item, User $viewer): array
    {
        $item->loadMissing('requester', 'coach', 'memberProfile.profession', 'project', 'sessions', 'review');

        return [
            'id' => $item->id,
            'kind' => ['value' => $item->kind->value, 'label' => $item->kind->label()],
            'status' => ['value' => $item->status->value, 'label' => $item->status->label()],
            'role' => $viewer->id === $item->coach_id ? 'coach' : 'accompagne',
            'coach' => ['id' => $item->coach->id, 'name' => $item->coach->name],
            'requester' => ['id' => $item->requester->id, 'name' => $item->requester->name],
            'profile' => $item->memberProfile?->label(),
            'project' => $item->project ? ['id' => $item->project->id, 'title' => $item->project->title] : null,
            'objectives' => $item->objectives,
            'decline_reason' => $item->decline_reason,
            'sessions' => $item->sessions->map(fn ($session) => [
                'held_on' => $session->held_on->toDateString(),
                'summary' => $session->summary,
                'next_steps' => $session->next_steps,
            ])->values(),
            'review' => $item->review ? self::presentReview($item->review) : null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
