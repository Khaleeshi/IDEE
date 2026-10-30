<?php

namespace App\Services\Documents;

use App\Enums\DocumentAccessStatus;
use App\Enums\DocumentLogAction;
use App\Models\DocumentAccess;
use App\Models\DocumentLog;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Notifications\DocumentAccessRequested;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Documents privés d'un projet : visibles seulement après l'accord explicite
 * du porteur ; chaque accès est journalisé et révocable (PR-05, RG-05).
 */
class DocumentService
{
    public function upload(Project $project, User $owner, string $title, UploadedFile $file): ProjectDocument
    {
        $document = new ProjectDocument(['title' => $title]);
        $document->project()->associate($project);
        $document->owner()->associate($owner);
        $document->forceFill([
            'path' => $file->store("documents/{$project->id}", 'local'),
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ])->save();

        $project->recordActivity();

        return $document;
    }

    public function delete(ProjectDocument $document): void
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();
    }

    /**
     * @throws ValidationException
     */
    public function requestAccess(Project $project, User $user): DocumentAccess
    {
        if ($project->isOwnedBy($user)) {
            throw ValidationException::withMessages(['access' => 'Ce sont vos propres documents.']);
        }

        $access = $this->accessOf($user, $project) ?? new DocumentAccess;
        $access->forceFill(['project_id' => $project->id, 'user_id' => $user->id]);

        if (in_array($access->status, [DocumentAccessStatus::Demandee, DocumentAccessStatus::Accordee], true)) {
            throw ValidationException::withMessages(['access' => $access->status === DocumentAccessStatus::Accordee
                ? 'Vous avez déjà accès à ces documents.'
                : 'Votre demande attend la réponse du porteur.']);
        }

        $access->forceFill(['status' => DocumentAccessStatus::Demandee, 'decided_at' => null])->save();
        $this->log($project, $user, $user, DocumentLogAction::Demande);
        $project->owner->notify(new DocumentAccessRequested($access));

        return $access;
    }

    /**
     * Le porteur accorde ou refuse l'accès demandé.
     *
     * @throws ValidationException
     */
    public function decide(DocumentAccess $access, User $owner, bool $granted): DocumentAccess
    {
        if ($access->status !== DocumentAccessStatus::Demandee) {
            throw ValidationException::withMessages(['access' => 'Cette demande a déjà reçu une réponse.']);
        }

        $access->forceFill([
            'status' => $granted ? DocumentAccessStatus::Accordee : DocumentAccessStatus::Refusee,
            'decided_at' => now(),
        ])->save();
        $this->log($access->project, $access->user, $owner, $granted ? DocumentLogAction::Accord : DocumentLogAction::Refus);

        return $access;
    }

    /**
     * @throws ValidationException
     */
    public function revoke(DocumentAccess $access, User $owner): DocumentAccess
    {
        if ($access->status !== DocumentAccessStatus::Accordee) {
            throw ValidationException::withMessages(['access' => 'Cet accès n\'est pas actif.']);
        }

        $access->forceFill(['status' => DocumentAccessStatus::Revoquee, 'decided_at' => now()])->save();
        $this->log($access->project, $access->user, $owner, DocumentLogAction::Revocation);

        return $access;
    }

    public function canRead(?User $user, Project $project): bool
    {
        if ($user === null) {
            return false;
        }

        return $project->isOwnedBy($user) || $project->documentAccesses()
            ->where('user_id', $user->id)
            ->where('status', DocumentAccessStatus::Accordee)
            ->exists();
    }

    public function accessOf(?User $user, Project $project): ?DocumentAccess
    {
        return $user ? $project->documentAccesses()->where('user_id', $user->id)->first() : null;
    }

    /**
     * Enregistre la consultation d'un document par un membre autorisé.
     */
    public function recordRead(ProjectDocument $document, User $reader): void
    {
        if (! $document->project->isOwnedBy($reader)) {
            $this->log($document->project, $reader, $reader, DocumentLogAction::Consultation, $document);
        }
    }

    /**
     * Derniers événements du journal d'accès, pour le porteur.
     *
     * @return Collection<int, DocumentLog>
     */
    public function logFor(Project $project, int $limit = 20): Collection
    {
        return DocumentLog::where('project_id', $project->id)
            ->with(['user', 'document'])
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function log(Project $project, User $user, User $actor, DocumentLogAction $action, ?ProjectDocument $document = null): void
    {
        $log = new DocumentLog;
        $log->forceFill([
            'project_id' => $project->id,
            'project_document_id' => $document?->id,
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'action' => $action,
        ])->save();
    }
}
