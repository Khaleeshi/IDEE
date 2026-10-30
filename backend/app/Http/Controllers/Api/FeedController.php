<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Services\Feed\FeedService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    /**
     * Fil d'activité public (FC-01).
     */
    public function index(FeedService $feed): AnonymousResourceCollection
    {
        return ActivityResource::collection($feed->query()->paginate(20));
    }
}
