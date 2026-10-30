<?php

namespace App\Http\Controllers\Web;

use App\Enums\HelpOfferKind;
use App\Enums\NeedStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseNeedRequest;
use App\Http\Requests\RespondHelpOfferRequest;
use App\Http\Requests\StoreHelpOfferRequest;
use App\Http\Requests\StoreNeedRequest;
use App\Models\HelpOffer;
use App\Models\Need;
use App\Models\Project;
use App\Services\Needs\NeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NeedController extends Controller
{
    public function __construct(private readonly NeedService $needs) {}

    public function store(StoreNeedRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->needs->create($project, $request->validated());

        return back()->with('status', 'Appel à action publié.');
    }

    public function close(CloseNeedRequest $request, Need $need): RedirectResponse
    {
        Gate::authorize('update', $need);

        $this->needs->close($need, NeedStatus::from($request->validated('status')), $request->validated('outcome'));

        return back()->with('status', 'Besoin clôturé.');
    }

    public function offerHelp(StoreHelpOfferRequest $request, Need $need): RedirectResponse
    {
        $this->needs->offerHelp($need, $request->user(), $request->validated('message'), $request->kind());

        return back()->with('status', $request->kind() === HelpOfferKind::Aide
            ? 'Votre proposition a été transmise au porteur du projet.'
            : 'Merci ! Le porteur du projet sait que vous connaissez quelqu\'un qui peut l\'aider.');
    }

    public function respondToOffer(RespondHelpOfferRequest $request, HelpOffer $helpOffer): RedirectResponse
    {
        Gate::authorize('respond', $helpOffer);

        $accepted = $request->boolean('accepted');
        $this->needs->respondToOffer($helpOffer, $accepted);

        return back()->with('status', $accepted
            ? 'Proposition acceptée. Contactez maintenant cette personne pour avancer ensemble.'
            : 'Proposition déclinée.');
    }
}
