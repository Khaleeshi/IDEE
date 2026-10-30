<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\NeedResource;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Services\Feed\FeedService;
use App\Services\Network\FollowService;
use App\Services\Network\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NetworkController extends Controller
{
    public function __construct(private readonly FollowService $follows) {}

    public function followProject(Request $request, Project $project): JsonResponse
    {
        return response()->json(['following' => $this->follows->toggle($request->user(), $project)]);
    }

    public function followMember(Request $request, User $user): JsonResponse
    {
        return response()->json(['following' => $this->follows->toggle($request->user(), $user)]);
    }

    public function followSector(Request $request, Sector $sector): JsonResponse
    {
        return response()->json(['following' => $this->follows->toggle($request->user(), $sector)]);
    }

    /**
     * Fil limité aux projets, personnes et secteurs suivis (FC-02).
     */
    public function followedFeed(Request $request, FeedService $feed): AnonymousResourceCollection
    {
        return ActivityResource::collection($feed->query($request->user())->paginate(20));
    }

    /**
     * Recommandations du jour, chacune avec ses raisons (RR-02, RR-03).
     */
    public function recommendations(Request $request, RecommendationService $recommendations): JsonResponse
    {
        return response()->json(['data' => $recommendations->forUser($request->user())->map(fn (array $pick) => [
            'need' => new NeedResource($pick['need']),
            'project' => ['id' => $pick['need']->project->id, 'title' => $pick['need']->project->title],
            'reasons' => $pick['reasons'],
        ])]);
    }
}
