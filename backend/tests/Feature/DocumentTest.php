<?php

namespace Tests\Feature;

use App\Models\DocumentAccess;
use App\Models\DocumentLog;
use App\Models\Project;
use App\Models\User;
use App\Notifications\DocumentAccessRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function upload(Project $project): string
    {
        Sanctum::actingAs($project->owner);

        return $this->post("/api/projects/{$project->id}/documents", [
            'title' => 'Business plan 2026',
            'file' => UploadedFile::fake()->create('plan.pdf', 300, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
    }

    public function test_documents_are_shared_only_with_the_owners_consent_and_every_access_is_logged(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $documentId = $this->upload($project);
        $investor = User::factory()->create();

        Sanctum::actingAs($investor);
        $this->getJson("/api/projects/{$project->id}/documents")->assertForbidden();
        $this->getJson("/api/documents/{$documentId}")->assertForbidden();
        $accessId = $this->postJson("/api/projects/{$project->id}/document-accesses")->assertCreated()->json('data.id');
        $this->postJson("/api/projects/{$project->id}/document-accesses")->assertUnprocessable();
        $this->postJson("/api/document-accesses/{$accessId}/decide", ['granted' => true])->assertForbidden();
        Notification::assertSentTo($project->owner, DocumentAccessRequested::class);

        Sanctum::actingAs($project->owner);
        $this->getJson("/api/projects/{$project->id}/document-accesses")->assertJsonPath('data.0.status.value', 'demandee');
        $this->postJson("/api/document-accesses/{$accessId}/decide", ['granted' => true])->assertJsonPath('data.status.label', 'Accordée');

        Sanctum::actingAs($investor);
        $this->getJson("/api/projects/{$project->id}/documents")->assertOk()->assertJsonPath('data.0.title', 'Business plan 2026');
        $this->get("/api/documents/{$documentId}")->assertOk()->assertDownload('plan.pdf');

        Sanctum::actingAs($project->owner);
        $this->postJson("/api/document-accesses/{$accessId}/revoke")->assertJsonPath('data.status.value', 'revoquee');

        Sanctum::actingAs($investor);
        $this->getJson("/api/documents/{$documentId}")->assertForbidden();

        $this->assertSame(
            ['demande', 'accord', 'consultation', 'revocation'],
            DocumentLog::oldest('id')->pluck('action')->map->value->all(),
        );
    }

    public function test_only_the_owner_uploads_and_deletes_documents(): void
    {
        $project = Project::factory()->create();
        $documentId = $this->upload($project);

        Sanctum::actingAs(User::factory()->create());
        $this->post("/api/projects/{$project->id}/documents", [
            'title' => 'Faux document',
            'file' => UploadedFile::fake()->create('faux.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertForbidden();
        $this->deleteJson("/api/documents/{$documentId}")->assertForbidden();

        Sanctum::actingAs($project->owner);
        $this->deleteJson("/api/documents/{$documentId}")->assertNoContent();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_project_page_offers_to_request_access(): void
    {
        $project = Project::factory()->create();
        $this->upload($project);
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('projects.show', $project))
            ->assertSee('Documents privés')
            ->assertSee('1 document privé')
            ->assertDontSee('Business plan 2026')
            ->assertSee("Demander l'accès aux documents", false);

        $this->post(route('document-access.request', $project))->assertRedirect(route('projects.show', $project).'#documents');

        $access = DocumentAccess::firstOrFail();
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('Accorder')->assertSee("Journal d'accès", false);
        $this->post(route('document-access.decide', $access), ['granted' => 1]);

        $this->actingAs($member)->get(route('projects.show', $project))->assertSee('Business plan 2026');
    }
}
