<?php

namespace App\Http\Controllers\Api;

use App\Enums\NeedStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseNeedRequest;
use App\Http\Requests\StoreNeedRequest;
use App\Http\Resources\NeedResource;
use App\Models\Need;
use App\Models\Project;
use App\Services\Needs\NeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class NeedController extends Controller
{
    public function __construct(private readonly NeedService $needs) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        abort_unless(Gate::forUser(request()->user('sanctum'))->allows('view', $project), Response::HTTP_NOT_FOUND);

        return NeedResource::collection(
            $project->needs()->withCount('helpOffers')->latest()->get()
        );
    }

    public function store(StoreNeedRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $need = $this->needs->create($project, $request->validated());

        return (new NeedResource($need))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function close(CloseNeedRequest $request, Need $need): NeedResource
    {
        Gate::authorize('update', $need);

        return new NeedResource($this->needs->close(
            $need,
            NeedStatus::from($request->validated('status')),
            $request->validated('outcome'),
        ));
    }
}
