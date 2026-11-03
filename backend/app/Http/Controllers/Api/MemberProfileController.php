<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileRequest;
use App\Http\Resources\MemberProfileResource;
use App\Models\MemberProfile;
use App\Services\Members\MemberProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MemberProfileController extends Controller
{
    public function __construct(private readonly MemberProfileService $profiles) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return MemberProfileResource::collection($request->user()->memberProfiles()->with('profession.category', 'user')->get());
    }

    public function store(MemberProfileRequest $request): MemberProfileResource
    {
        $profile = $this->profiles->create($request->user(), $request->validated());

        return new MemberProfileResource($profile->load('profession.category', 'user'));
    }

    public function update(MemberProfileRequest $request, MemberProfile $memberProfile): MemberProfileResource
    {
        Gate::authorize('update', $memberProfile);

        return new MemberProfileResource($this->profiles->update($memberProfile, $request->validated())->load('profession.category', 'user'));
    }

    public function destroy(MemberProfile $memberProfile): Response
    {
        Gate::authorize('delete', $memberProfile);
        $this->profiles->delete($memberProfile);

        return response()->noContent();
    }
}
