<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectDocumentRequest;
use App\Http\Resources\DocumentAccessResource;
use App\Http\Resources\ProjectDocumentResource;
use App\Models\DocumentAccess;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    /**
     * Documents d'un projet, pour le porteur et les membres autorisés (RG-05).
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        abort_unless($this->documents->canRead($request->user(), $project), Response::HTTP_FORBIDDEN);

        return ProjectDocumentResource::collection($project->documents);
    }

    public function store(StoreProjectDocumentRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $document = $this->documents->upload($project, $request->user(), $request->validated('title'), $request->file('file'));

        return (new ProjectDocumentResource($document))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, ProjectDocument $projectDocument): StreamedResponse
    {
        abort_unless($this->documents->canRead($request->user(), $projectDocument->project), Response::HTTP_FORBIDDEN);

        $this->documents->recordRead($projectDocument, $request->user());

        return Storage::disk('local')->download($projectDocument->path, $projectDocument->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(ProjectDocument $projectDocument): Response
    {
        Gate::authorize('update', $projectDocument->project);

        $this->documents->delete($projectDocument);

        return response()->noContent();
    }

    /**
     * Demandes et autorisations d'accès, pour le porteur.
     */
    public function accesses(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('update', $project);

        return DocumentAccessResource::collection($project->documentAccesses()->with('user.roleAssignments')->latest()->get());
    }

    public function requestAccess(Request $request, Project $project): JsonResponse
    {
        $access = $this->documents->requestAccess($project, $request->user(), $request->boolean('accept_nda'));

        return (new DocumentAccessResource($access))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function decide(Request $request, DocumentAccess $documentAccess): DocumentAccessResource
    {
        Gate::authorize('update', $documentAccess->project);
        $request->validate(['granted' => ['required', 'boolean']]);

        return new DocumentAccessResource($this->documents->decide($documentAccess, $request->user(), $request->boolean('granted')));
    }

    public function revoke(Request $request, DocumentAccess $documentAccess): DocumentAccessResource
    {
        Gate::authorize('update', $documentAccess->project);

        return new DocumentAccessResource($this->documents->revoke($documentAccess, $request->user()));
    }
}
