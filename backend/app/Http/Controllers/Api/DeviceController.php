<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Appareils connectés (CP-06).
 */
class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $devices) {}

    public function index(Request $request): JsonResponse
    {
        $devices = $this->devices->list($request->user(), $this->currentTokenId($request))->map(fn (array $device) => [
            ...$device,
            'last_active_at' => $device['last_active_at']?->toIso8601String(),
            'signed_in_at' => $device['signed_in_at']?->toIso8601String(),
        ]);

        return response()->json(['data' => $devices]);
    }

    public function destroy(Request $request, string $device): Response|JsonResponse
    {
        return $this->devices->revoke($request->user(), $device)
            ? response()->noContent()
            : response()->json(['message' => 'Cet appareil n\'est plus connecté à votre compte.'], 404);
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $count = $this->devices->revokeOthers($request->user(), $this->currentTokenId($request));

        return response()->json(['message' => $count > 0 ? "{$count} appareil(s) déconnecté(s)." : 'Aucun autre appareil n\'était connecté.']);
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token->id : null;
    }
}
