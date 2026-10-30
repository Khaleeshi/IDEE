<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\ProfileResource;
use App\Services\Auth\OneTimeCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private readonly OneTimeCodeService $codes) {}

    public function requestCode(RequestCodeRequest $request): JsonResponse
    {
        $this->codes->issue($request->validated('phone'));

        return response()->json([
            'message' => 'Un code de connexion vous a été envoyé. Il est valable '.config('idee.otp.ttl_minutes').' minutes.',
        ], Response::HTTP_ACCEPTED);
    }

    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        $user = $this->codes->verify(
            $request->validated('phone'),
            $request->validated('code'),
            $request->validated('name'),
        );

        $token = $user->createToken($request->validated('device_name') ?? 'application')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new ProfileResource($user->load('roleAssignments')),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
