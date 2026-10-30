<?php

namespace App\Http\Controllers\Web;

use App\Enums\OpportunityKind;
use App\Enums\OrganisationKind;
use App\Enums\OrganisationRole;
use App\Enums\ProjectStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddOrganisationMemberRequest;
use App\Http\Requests\OrganisationRequest;
use App\Http\Requests\OrganisationVerificationRequest;
use App\Http\Requests\ProgrammeRequest;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\OrganisationMember;
use App\Models\Sector;
use App\Services\Organisations\OrganisationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pages d'organisation (CP-05), vérification (CF-03) et programmes publiés (OR-01).
 */
class OrganisationController extends Controller
{
    public function __construct(private readonly OrganisationService $organisations) {}

    public function index(Request $request): View
    {
        $kind = OrganisationKind::tryFrom((string) $request->query('type'));

        return view('organisations.index', [
            'organisations' => Organisation::query()
                ->when($kind, fn ($query) => $query->where('kind', $kind))
                ->orderByRaw("verification_status = 'verifiee' desc")
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'kinds' => OrganisationKind::cases(),
            'kind' => $kind,
        ]);
    }

    public function create(): View
    {
        return view('organisations.form', ['organisation' => new Organisation, 'kinds' => OrganisationKind::cases()]);
    }

    public function store(OrganisationRequest $request): RedirectResponse
    {
        $organisation = $this->organisations->create($request->user(), $request->validated());

        return redirect()->route('organisations.manage', $organisation)->with('status', 'Page créée. Ajoutez vos collègues et demandez la vérification.');
    }

    public function show(Request $request, Organisation $organisation): View
    {
        return view('organisations.show', [
            'organisation' => $organisation->load('members.user'),
            'programmes' => $organisation->opportunities()->open()->get(),
            'canManage' => $organisation->isManagedBy($request->user()),
        ]);
    }

    public function manage(Organisation $organisation): View
    {
        Gate::authorize('update', $organisation);

        return view('organisations.manage', [
            'organisation' => $organisation->load('members.user'),
            'programmes' => $organisation->opportunities,
            'kinds' => OrganisationKind::cases(),
            'roles' => OrganisationRole::cases(),
        ]);
    }

    public function update(OrganisationRequest $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', $organisation);

        $this->organisations->update($organisation, $request->validated());

        return redirect()->route('organisations.manage', $organisation)->with('status', 'Page mise à jour.');
    }

    public function addMember(AddOrganisationMemberRequest $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', $organisation);

        $member = $this->organisations->addMember($organisation, $request->user(), $request->validated('identifier'), OrganisationRole::from($request->validated('role')));

        return redirect()->route('organisations.manage', $organisation)->with('status', $member->user->name.' a rejoint la page.');
    }

    public function removeMember(OrganisationMember $organisationMember): RedirectResponse
    {
        Gate::authorize('update', $organisationMember->organisation);

        $this->organisations->removeMember($organisationMember);

        return redirect()->route('organisations.manage', $organisationMember->organisation)->with('status', 'Membre retiré de la page.');
    }

    public function requestVerification(OrganisationVerificationRequest $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', $organisation);

        $this->organisations->requestVerification($organisation, $request->validated('rccm'), $request->validated('ninea'), $request->file('document'));

        return redirect()->route('organisations.manage', $organisation)->with('status', 'Demande de vérification envoyée. Le justificatif sera effacé après examen.');
    }

    public function createProgramme(Organisation $organisation): View
    {
        Gate::authorize('update', $organisation);

        return view('organisations.programme', ['organisation' => $organisation, 'programme' => new Opportunity, ...$this->programmeOptions()]);
    }

    public function storeProgramme(ProgrammeRequest $request, Organisation $organisation): RedirectResponse
    {
        Gate::authorize('update', $organisation);

        $programme = $this->organisations->saveProgramme($organisation, $request->validated());

        return redirect()->route('opportunities.show', $programme)->with('status', 'Programme publié dans le répertoire.');
    }

    public function editProgramme(Opportunity $opportunity): View
    {
        $organisation = $this->programmeOrganisation($opportunity);

        return view('organisations.programme', ['organisation' => $organisation, 'programme' => $opportunity, ...$this->programmeOptions()]);
    }

    public function updateProgramme(ProgrammeRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $this->organisations->saveProgramme($this->programmeOrganisation($opportunity), $request->validated(), $opportunity);

        return redirect()->route('opportunities.show', $opportunity)->with('status', 'Programme mis à jour.');
    }

    public function destroyProgramme(Opportunity $opportunity): RedirectResponse
    {
        $organisation = $this->programmeOrganisation($opportunity);
        $opportunity->delete();

        return redirect()->route('organisations.manage', $organisation)->with('status', 'Programme retiré du répertoire.');
    }

    private function programmeOrganisation(Opportunity $opportunity): Organisation
    {
        $organisation = $opportunity->organisationPage;
        abort_if($organisation === null, 404);
        Gate::authorize('update', $organisation);

        return $organisation;
    }

    /**
     * @return array<string, mixed>
     */
    private function programmeOptions(): array
    {
        return [
            'kinds' => OpportunityKind::cases(),
            'stages' => ProjectStage::cases(),
            'sectors' => Sector::options('slug'),
        ];
    }
}
