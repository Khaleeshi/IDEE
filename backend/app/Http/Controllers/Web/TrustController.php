<?php

namespace App\Http\Controllers\Web;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppealRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\StoreVerificationRequest;
use App\Models\Report;
use App\Services\Moderation\ModerationService;
use App\Services\Trust\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Côté membre : signaler, connaître une décision et la contester, faire vérifier son identité.
 */
class TrustController extends Controller
{
    public function report(StoreReportRequest $request, ModerationService $moderation): RedirectResponse
    {
        $moderation->report($request->user(), $request->target(), ReportReason::from($request->validated('reason')), $request->validated('details'));

        return back()->with('status', 'Merci. L\'équipe IDEE examine ce signalement ; la personne concernée n\'est pas informée de votre nom.');
    }

    public function decision(Request $request, Report $report): View
    {
        abort_unless($report->target_user_id === $request->user()->id && $report->decision?->informsTarget(), 404);

        return view('moderation.decision', ['report' => $report]);
    }

    public function appeal(StoreAppealRequest $request, Report $report, ModerationService $moderation): RedirectResponse
    {
        $moderation->appeal($report, $request->user(), $request->validated('appeal'));

        return redirect()->route('moderation.decision', $report)->with('status', 'Votre recours est enregistré. Un autre membre de l\'équipe va l\'examiner.');
    }

    public function verify(StoreVerificationRequest $request, VerificationService $verifications): RedirectResponse
    {
        $verifications->submit($request->user(), $request->file('document'));

        return redirect()->to(route('profile.edit').'#verification')
            ->with('status', 'Pièce reçue. L\'équipe la vérifie puis l\'efface ; vous serez prévenu du résultat.');
    }
}
