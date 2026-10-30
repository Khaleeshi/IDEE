<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppealRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\StoreVerificationRequest;
use App\Http\Resources\EnumResource;
use App\Models\Report;
use App\Models\Verification;
use App\Services\Moderation\ModerationService;
use App\Services\Trust\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Signalements, décisions de modération et vérification d'identité, côté membre.
 */
class TrustController extends Controller
{
    public function report(StoreReportRequest $request, ModerationService $moderation): JsonResponse
    {
        $report = $moderation->report($request->user(), $request->target(), ReportReason::from($request->validated('reason')), $request->validated('details'));

        return response()->json(['data' => ['id' => $report->id, 'status' => EnumResource::make($report->status)]], Response::HTTP_CREATED);
    }

    /**
     * Décisions de modération qui concernent le membre, avec leur motif.
     */
    public function decisions(Request $request, ModerationService $moderation): JsonResponse
    {
        return response()->json(['data' => $moderation->decisionsAbout($request->user())->map(fn (Report $report) => $this->decision($report))]);
    }

    public function appeal(StoreAppealRequest $request, Report $report, ModerationService $moderation): JsonResponse
    {
        return response()->json(['data' => $this->decision($moderation->appeal($report, $request->user(), $request->validated('appeal')))]);
    }

    public function verification(Request $request, VerificationService $verifications): JsonResponse
    {
        return response()->json(['data' => $this->verificationData($verifications->latestFor($request->user()))]);
    }

    public function verify(StoreVerificationRequest $request, VerificationService $verifications): JsonResponse
    {
        $verification = $verifications->submit($request->user(), $request->file('document'));

        return response()->json(['data' => $this->verificationData($verification)], Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function decision(Report $report): array
    {
        return [
            'id' => $report->id,
            'reason' => EnumResource::make($report->reason),
            'decision' => EnumResource::make($report->decision),
            'note' => $report->decision_note,
            'decided_at' => $report->decided_at?->toIso8601String(),
            'status' => EnumResource::make($report->status),
            'can_appeal' => $report->canBeAppealed(),
            'appeal_upheld' => $report->appeal_upheld,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function verificationData(?Verification $verification): ?array
    {
        return $verification ? [
            'id' => $verification->id,
            'status' => EnumResource::make($verification->status),
            'refusal_reason' => $verification->refusal_reason,
            'expires_at' => $verification->expires_at?->toIso8601String(),
        ] : null;
    }
}
