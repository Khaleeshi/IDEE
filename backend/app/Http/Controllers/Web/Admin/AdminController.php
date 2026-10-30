<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\ModerationDecision;
use App\Enums\OpportunityKind;
use App\Enums\OrganisationVerification;
use App\Enums\ProjectStage;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityRequest;
use App\Models\JourneyStep;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\Report;
use App\Models\Sector;
use App\Models\Verification;
use App\Services\Admin\IndicatorService;
use App\Services\Journeys\JourneyService;
use App\Services\Moderation\ModerationService;
use App\Services\Organisations\OrganisationService;
use App\Services\Trust\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Administration de l'équipe IDEE : indicateurs (AD-03), modération (AD-01),
 * vérifications (AD-04), fiches pratiques et opportunités (AD-02).
 */
class AdminController extends Controller
{
    public function dashboard(IndicatorService $indicators, ModerationService $moderation, VerificationService $verifications, JourneyService $journeys): View
    {
        return view('admin.dashboard', [
            'indicators' => $indicators->all(),
            'openReports' => $moderation->queue()->count() + $moderation->queue(ReportStatus::EnRecours)->count(),
            'pendingVerifications' => $verifications->pending()->count(),
            'staleSteps' => $journeys->staleSteps()->count(),
        ]);
    }

    public function reports(ModerationService $moderation): View
    {
        return view('admin.reports', [
            'open' => $moderation->queue(),
            'appeals' => $moderation->queue(ReportStatus::EnRecours),
        ]);
    }

    public function report(Report $report): View
    {
        return view('admin.report', [
            'report' => $report->load(['reporter', 'targetUser', 'reportable', 'moderator']),
            'decisions' => ModerationDecision::cases(),
            'history' => Report::where('target_user_id', $report->target_user_id)->whereKeyNot($report->id)->whereNotNull('decision')->latest()->limit(10)->get(),
        ]);
    }

    public function decide(Request $request, Report $report, ModerationService $moderation): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::enum(ModerationDecision::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $moderation->decide($report, $request->user(), ModerationDecision::from($data['decision']), $data['note'] ?? null);

        return redirect()->route('admin.reports')->with('status', 'Décision enregistrée.');
    }

    public function reviewAppeal(Request $request, Report $report, ModerationService $moderation): RedirectResponse
    {
        $request->validate(['upheld' => ['required', 'boolean']]);

        $moderation->reviewAppeal($report, $request->user(), $request->boolean('upheld'));

        return redirect()->route('admin.reports')->with('status', 'Recours examiné.');
    }

    public function verifications(VerificationService $verifications): View
    {
        return view('admin.verifications', ['pending' => $verifications->pending()]);
    }

    public function verificationDocument(Verification $verification): StreamedResponse
    {
        abort_if($verification->document_path === null, 404);

        return Storage::disk('local')->response($verification->document_path, $verification->document_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function decideVerification(Request $request, Verification $verification, VerificationService $verifications): RedirectResponse
    {
        $data = $request->validate([
            'approved' => ['required', 'boolean'],
            'reason' => ['required_if:approved,0', 'nullable', 'string', 'max:500'],
        ], ['reason.required_if' => 'Indiquez le motif du refus : le membre le recevra.']);

        $request->boolean('approved')
            ? $verifications->approve($verification, $request->user())
            : $verifications->refuse($verification, $request->user(), $data['reason']);

        return redirect()->route('admin.verifications')->with('status', 'Vérification traitée.');
    }

    /**
     * CF-03 : organisations en attente de vérification de leur RCCM et NINEA.
     */
    public function organisations(): View
    {
        return view('admin.organisations', [
            'pending' => Organisation::where('verification_status', OrganisationVerification::EnAttente)->oldest('verification_requested_at')->get(),
        ]);
    }

    public function organisationDocument(Organisation $organisation): StreamedResponse
    {
        abort_if($organisation->verification_document_path === null, 404);

        return Storage::disk('local')->response($organisation->verification_document_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function decideOrganisation(Request $request, Organisation $organisation, OrganisationService $organisations): RedirectResponse
    {
        $request->validate(['approved' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:500']]);

        $organisations->decideVerification($organisation, $request->boolean('approved'), $request->input('note'));

        return redirect()->route('admin.organisations')->with('status', 'Vérification traitée.');
    }

    public function opportunities(): View
    {
        return view('admin.opportunities', ['opportunities' => Opportunity::orderByDesc('is_published')->orderBy('title')->get()]);
    }

    public function createOpportunity(): View
    {
        return view('admin.opportunity', ['opportunity' => new Opportunity(['is_published' => true]), ...$this->opportunityOptions()]);
    }

    public function storeOpportunity(OpportunityRequest $request): RedirectResponse
    {
        $opportunity = new Opportunity(['slug' => Str::slug($request->validated('title')).'-'.Str::lower(Str::random(6))]);

        return $this->saveOpportunity($request, $opportunity, 'Opportunité ajoutée au répertoire.');
    }

    public function editOpportunity(Opportunity $opportunity): View
    {
        return view('admin.opportunity', ['opportunity' => $opportunity, ...$this->opportunityOptions()]);
    }

    public function updateOpportunity(OpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        return $this->saveOpportunity($request, $opportunity, 'Opportunité mise à jour.');
    }

    public function destroyOpportunity(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()->route('admin.opportunities')->with('status', 'Opportunité supprimée.');
    }

    /**
     * Toute fiche saisie ou corrigée par l'équipe est datée du jour et n'est plus écrasée à la mise en ligne.
     */
    private function saveOpportunity(OpportunityRequest $request, Opportunity $opportunity, string $status): RedirectResponse
    {
        $opportunity->fill([
            ...$request->validated(),
            // OP-02 : sans case cochée, l'opportunité reste ouverte à tous les stades et secteurs.
            'stages' => $request->validated('stages') ?: null,
            'sectors' => $request->validated('sectors') ?: null,
            'is_published' => $request->boolean('is_published'),
            'checked_on' => today(),
        ]);
        $opportunity->edited_at = now();
        $opportunity->save();

        return redirect()->route('admin.opportunities')->with('status', $status);
    }

    public function guides(): View
    {
        return view('admin.guides', [
            'steps' => JourneyStep::with('journey')->get()->sortBy([
                fn (JourneyStep $a, JourneyStep $b) => $b->isStale() <=> $a->isStale(),
                fn (JourneyStep $a, JourneyStep $b) => [$a->journey->position, $a->position] <=> [$b->journey->position, $b->position],
            ]),
        ]);
    }

    public function editGuide(JourneyStep $journeyStep): View
    {
        return view('admin.guide', ['step' => $journeyStep->load('journey')]);
    }

    public function updateGuide(Request $request, JourneyStep $journeyStep): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'documents' => ['nullable', 'string', 'max:3000'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'string', 'max:255'],
            'delay' => ['nullable', 'string', 'max:255'],
            'source_label' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'needs_expert' => ['boolean'],
            'checked' => ['boolean'],
        ]);

        $journeyStep->fill([
            ...collect($data)->except(['documents', 'checked'])->all(),
            'documents' => array_values(array_filter(array_map('trim', explode("\n", $data['documents'] ?? '')))) ?: null,
            'needs_expert' => $request->boolean('needs_expert'),
        ]);

        // Cocher « vérifiée » date la fiche du jour (RG-10).
        if ($request->boolean('checked')) {
            $journeyStep->checked_on = today();
        }

        $journeyStep->edited_at = now();
        $journeyStep->save();

        return redirect()->route('admin.guides')->with('status', 'Fiche mise à jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opportunityOptions(): array
    {
        return [
            'kinds' => OpportunityKind::cases(),
            'stages' => ProjectStage::cases(),
            'sectors' => Sector::options('slug'),
        ];
    }
}
