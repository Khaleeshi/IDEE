<?php

namespace App\Http\Controllers\Web;

use App\Enums\MeetingMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organisation;
use App\Models\Project;
use App\Services\Events\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sessions de pitch en ligne et en présentiel (EV-01).
 */
class EventController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    public function index(Request $request): View
    {
        return view('events.index', [
            'events' => Event::upcoming()->with('organisation')->paginate(20),
            'canOrganise' => $request->user() !== null && $this->events->canOrganise($request->user()),
        ]);
    }

    public function show(Request $request, Event $event): View
    {
        $user = $request->user();
        $registration = $user ? $event->registrations()->where('user_id', $user->id)->first() : null;
        $canManage = $this->events->canManage($user, $event);

        return view('events.show', [
            'event' => $event->load('organisation'),
            'registration' => $registration,
            'myProjects' => $user ? $user->projects()->active()->get() : collect(),
            'canManage' => $canManage,
            // Le lien de la visio n'est donné qu'aux inscrits et aux organisateurs.
            'showPlace' => $event->mode !== MeetingMode::Visio || $canManage || $registration?->status->attends(),
            'candidates' => $canManage ? $event->registrations()->where('kind', 'pitch')->with(['user', 'project'])->oldest()->get() : collect(),
            'attendees' => $event->registrations()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->events->canOrganise($request->user()), 403);

        return view('events.form', ['organisations' => $this->events->organisationsFor($request->user())]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = $this->events->create(
            $request->user(),
            $request->safe()->except('organisation_id'),
            $request->filled('organisation_id') ? Organisation::findOrFail($request->validated('organisation_id')) : null,
        );

        return redirect()->route('events.show', $event)->with('status', 'Session publiée.');
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        $project = $request->filled('project_id') ? Project::findOrFail($request->input('project_id')) : null;
        $this->events->register($event, $request->user(), $project);

        return redirect()->route('events.show', $event)->with('status', $project
            ? 'Candidature envoyée. L\'organisateur vous dira si votre projet est retenu.'
            : 'Vous êtes inscrit. Un rappel vous sera envoyé la veille.');
    }

    public function unregister(Request $request, Event $event): RedirectResponse
    {
        $this->events->unregister($event, $request->user());

        return redirect()->route('events.show', $event)->with('status', 'Inscription annulée.');
    }

    public function decide(DecisionRequest $request, EventRegistration $eventRegistration): RedirectResponse
    {
        abort_unless($this->events->canManage($request->user(), $eventRegistration->event), 403);

        $this->events->decide($eventRegistration, $request->boolean('accepted'));

        return redirect()->to(route('events.show', $eventRegistration->event).'#candidatures')->with('status', 'Décision enregistrée et transmise.');
    }

    public function cancel(Request $request, Event $event): RedirectResponse
    {
        abort_unless($this->events->canManage($request->user(), $event), 403);

        $this->events->cancel($event);

        return redirect()->route('events.show', $event)->with('status', 'Session annulée ; les inscrits sont prévenus.');
    }
}
