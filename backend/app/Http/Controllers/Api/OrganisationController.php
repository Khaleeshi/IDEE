<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrganisationKind;
use App\Enums\OrganisationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddOrganisationMemberRequest;
use App\Http\Requests\OrganisationRequest;
use App\Http\Requests\OrganisationVerificationRequest;
use App\Http\Requests\ProgrammeRequest;
use App\Http\Resources\OpportunityResource;
use App\Http\Resources\OrganisationResource;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\OrganisationMember;
use App\Services\Organisations\OrganisationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Pages d'organisation (CP-05), vérification (CF-03) et programmes publiés (OR-01).
 */
class OrganisationController extends Controller
{
    public function __construct(private readonly OrganisationService $organisations) {}

    /**
     * Annuaire des organisations (`type`), les vérifiées d'abord.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $kind = OrganisationKind::tryFrom((string) $request->query('type'));

        return OrganisationResource::collection(Organisation::query()
            ->when($kind, fn ($query) => $query->where('kind', $kind))
            ->orderByRaw("verification_status = 'verifiee' desc")
            ->orderBy('name')
            ->paginate(30));
    }

    public function show(Organisation $organisation): OrganisationResource
    {
        return (new OrganisationResource($organisation->load('members.user')))
            ->additional(['programmes' => OpportunityResource::collection($organisation->opportunities()->open()->get())]);
    }

    public function store(OrganisationRequest $request): JsonResponse
    {
        $organisation = $this->organisations->create($request->user(), $request->validated());

        return (new OrganisationResource($organisation->load('members.user')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(OrganisationRequest $request, Organisation $organisation): OrganisationResource
    {
        Gate::authorize('update', $organisation);

        return new OrganisationResource($this->organisations->update($organisation, $request->validated()));
    }

    public function addMember(AddOrganisationMemberRequest $request, Organisation $organisation): JsonResponse
    {
        Gate::authorize('update', $organisation);

        $this->organisations->addMember($organisation, $request->user(), $request->validated('identifier'), OrganisationRole::from($request->validated('role')));

        return (new OrganisationResource($organisation->load('members.user')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function removeMember(OrganisationMember $organisationMember): Response
    {
        Gate::authorize('update', $organisationMember->organisation);

        $this->organisations->removeMember($organisationMember);

        return response()->noContent();
    }

    public function requestVerification(OrganisationVerificationRequest $request, Organisation $organisation): OrganisationResource
    {
        Gate::authorize('update', $organisation);

        return new OrganisationResource($this->organisations->requestVerification(
            $organisation,
            $request->validated('rccm'),
            $request->validated('ninea'),
            $request->file('document'),
        ));
    }

    public function storeProgramme(ProgrammeRequest $request, Organisation $organisation): JsonResponse
    {
        Gate::authorize('update', $organisation);

        return (new OpportunityResource($this->organisations->saveProgramme($organisation, $request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function updateProgramme(ProgrammeRequest $request, Opportunity $opportunity): OpportunityResource
    {
        return new OpportunityResource($this->organisations->saveProgramme($this->programmeOrganisation($opportunity), $request->validated(), $opportunity));
    }

    public function destroyProgramme(Opportunity $opportunity): Response
    {
        $this->programmeOrganisation($opportunity);
        $opportunity->delete();

        return response()->noContent();
    }

    private function programmeOrganisation(Opportunity $opportunity): Organisation
    {
        $organisation = $opportunity->organisationPage;
        abort_if($organisation === null, Response::HTTP_NOT_FOUND);
        Gate::authorize('update', $organisation);

        return $organisation;
    }
}
