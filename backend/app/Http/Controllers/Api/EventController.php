<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EnumResource;
use App\Models\Event;
use App\Models\EventPairing;
use App\Models\EventRegistration;
use App\Models\Organisation;
use App\Models\Project;
use App\Services\Events\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sessions de pitch en ligne et en présentiel (EV-01).
 */
class EventController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    public function index(): JsonResponse
    {
        $events = Event::upcoming()->with('organisation')->paginate(20);

        return response()->json([
            'data' => collect($events->items())->map(fn (Event $event) => $this->present($event, false)),
            'meta' => ['current_page' => $events->currentPage(), 'last_page' => $events->lastPage()],
        ]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $user = $request->user('sanctum');
        $registration = $user ? $event->registrations()->where('user_id', $user->id)->first() : null;
        $showPlace = $event->mode !== MeetingMode::Visio || $this->events->canManage($user, $event) || $registration?->status->attends();

        return response()->json(['data' => [
            ...$this->present($event->load('organisation'), (bool) $showPlace),
            'my_registration' => $registration ? $this->registration($registration) : null,
        ]]);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->events->create(
            $request->user(),
            $request->safe()->except('organisation_id'),
            $request->filled('organisation_id') ? Organisation::findOrFail($request->validated('organisation_id')) : null,
        );

        return response()->json(['data' => $this->present($event, true)], Response::HTTP_CREATED);
    }

    /**
     * S'inscrire pour assister, ou candidater avec `project_id` pour présenter.
     */
    public function register(Request $request, Event $event): JsonResponse
    {
        $project = $request->filled('project_id') ? Project::findOrFail($request->input('project_id')) : null;

        return response()->json(['data' => $this->registration($this->events->register($event, $request->user(), $project, $request->input('side')))], Response::HTTP_CREATED);
    }

    /**
     * Forme les binômes des rencontres ciblées (EV-02), pour l'organisateur.
     */
    public function pair(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->events->canManage($request->user(), $event), Response::HTTP_FORBIDDEN);

        return response()->json(['data' => $this->events->pair($event)->map($this->pairing(...))]);
    }

    /**
     * Mes rencontres : horaires, interlocuteur et lien de visio.
     */
    public function meetings(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => $event->pairings()
            ->where(fn ($query) => $query->where('first_user_id', $user->id)->orWhere('second_user_id', $user->id))
            ->with(['first', 'second'])
            ->get()
            ->map(fn (EventPairing $pairing) => [...$this->pairing($pairing), 'with' => ['id' => $pairing->partnerOf($user)?->id, 'name' => $pairing->partnerOf($user)?->name]])]);
    }

    public function unregister(Request $request, Event $event): Response
    {
        $this->events->unregister($event, $request->user());

        return response()->noContent();
    }

    /**
     * Candidatures, pour l'organisateur.
     */
    public function registrations(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->events->canManage($request->user(), $event), Response::HTTP_FORBIDDEN);

        return response()->json(['data' => $event->registrations()->with(['user', 'project'])->oldest()->get()->map(fn (EventRegistration $registration) => [
            ...$this->registration($registration),
            'user' => ['id' => $registration->user_id, 'name' => $registration->user->name],
            'project' => $registration->project ? ['id' => $registration->project->id, 'title' => $registration->project->title] : null,
        ])]);
    }

    public function decide(DecisionRequest $request, EventRegistration $eventRegistration): JsonResponse
    {
        abort_unless($this->events->canManage($request->user(), $eventRegistration->event), Response::HTTP_FORBIDDEN);

        return response()->json(['data' => $this->registration($this->events->decide($eventRegistration, $request->boolean('accepted')))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Event $event, bool $showPlace): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'format' => $event->format ?? 'pitch',
            'description' => $event->description,
            'mode' => EnumResource::make($event->mode),
            'starts_at' => $event->starts_at->toIso8601String(),
            'place' => $showPlace ? $event->place : null,
            'pitch_slots' => $event->pitch_slots,
            'apply_until' => $event->apply_until?->toDateString(),
            'accepts_pitches' => $event->acceptsPitches(),
            'cancelled' => $event->isCancelled(),
            'organisation' => $event->organisation ? ['id' => $event->organisation->id, 'name' => $event->organisation->name] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pairing(EventPairing $pairing): array
    {
        return [
            'id' => $pairing->id,
            'round' => $pairing->round,
            'starts_at' => $pairing->starts_at->toIso8601String(),
            'room_url' => $pairing->room_url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registration(EventRegistration $registration): array
    {
        return [
            'id' => $registration->id,
            'kind' => $registration->kind,
            'side' => $registration->side,
            'status' => EnumResource::make($registration->status),
            'project_id' => $registration->project_id,
        ];
    }
}
