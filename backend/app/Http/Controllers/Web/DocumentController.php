<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectDocumentRequest;
use App\Models\DocumentAccess;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\Documents\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function store(StoreProjectDocumentRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->documents->upload($project, $request->user(), $request->validated('title'), $request->file('file'));

        return $this->backToDocuments($project, 'Document déposé. Il reste privé tant que vous n\'accordez pas d\'accès.');
    }

    public function destroy(ProjectDocument $projectDocument): RedirectResponse
    {
        Gate::authorize('update', $projectDocument->project);

        $this->documents->delete($projectDocument);

        return $this->backToDocuments($projectDocument->project, 'Document supprimé.');
    }

    /**
     * Téléchargement, journalisé, réservé au porteur et aux membres autorisés.
     */
    public function show(Request $request, ProjectDocument $projectDocument): StreamedResponse
    {
        abort_unless($this->documents->canRead($request->user(), $projectDocument->project), 403);

        $this->documents->recordRead($projectDocument, $request->user());

        return Storage::disk('local')->download($projectDocument->path, $projectDocument->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function requestAccess(Request $request, Project $project): RedirectResponse
    {
        $this->documents->requestAccess($project, $request->user());

        return $this->backToDocuments($project, 'Demande envoyée au porteur du projet.');
    }

    public function decide(Request $request, DocumentAccess $documentAccess): RedirectResponse
    {
        Gate::authorize('update', $documentAccess->project);

        $granted = $request->boolean('granted');
        $this->documents->decide($documentAccess, $request->user(), $granted);

        return $this->backToDocuments($documentAccess->project, $granted ? 'Accès accordé.' : 'Accès refusé.');
    }

    public function revoke(Request $request, DocumentAccess $documentAccess): RedirectResponse
    {
        Gate::authorize('update', $documentAccess->project);

        $this->documents->revoke($documentAccess, $request->user());

        return $this->backToDocuments($documentAccess->project, 'Accès révoqué.');
    }

    private function backToDocuments(Project $project, string $status): RedirectResponse
    {
        return redirect()->to(route('projects.show', $project).'#documents')->with('status', $status);
    }
}
