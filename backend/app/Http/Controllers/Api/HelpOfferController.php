<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RespondHelpOfferRequest;
use App\Http\Requests\StoreHelpOfferRequest;
use App\Http\Resources\HelpOfferResource;
use App\Models\HelpOffer;
use App\Models\Need;
use App\Services\Needs\NeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class HelpOfferController extends Controller
{
    public function __construct(private readonly NeedService $needs) {}

    public function index(Need $need): AnonymousResourceCollection
    {
        Gate::authorize('viewHelpOffers', $need);

        return HelpOfferResource::collection(
            $need->helpOffers()->with('user.roleAssignments')->latest()->get()
        );
    }

    public function store(StoreHelpOfferRequest $request, Need $need): JsonResponse
    {
        $offer = $this->needs->offerHelp($need, $request->user(), $request->validated('message'), $request->kind());

        return (new HelpOfferResource($offer->load('user.roleAssignments')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function respond(RespondHelpOfferRequest $request, HelpOffer $helpOffer): HelpOfferResource
    {
        Gate::authorize('respond', $helpOffer);

        $offer = $this->needs->respondToOffer($helpOffer, $request->boolean('accepted'));

        return new HelpOfferResource($offer->load('user.roleAssignments'));
    }
}
