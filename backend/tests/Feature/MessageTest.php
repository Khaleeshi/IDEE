<?php

namespace Tests\Feature;

use App\Enums\ContactRequestStatus;
use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Messaging\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private const INTRO = 'Bonjour, je distribue des produits alimentaires à Dakar et votre farine m\'intéresse beaucoup. Pouvons-nous en parler cette semaine ?';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function conversation(bool $accepted = true): ContactRequest
    {
        $project = Project::factory()->create();
        $service = app(ContactRequestService::class);
        $request = $service->send(User::factory()->create(), $project->owner, self::INTRO, $project);

        if ($accepted) {
            $service->respond($request, $project->owner, ContactRequestStatus::Interesse);
        }

        return $request->fresh();
    }

    public function test_messaging_opens_only_once_both_sides_are_interested(): void
    {
        $request = $this->conversation(accepted: false);
        Sanctum::actingAs($request->sender);

        $this->postJson("/api/contact-requests/{$request->id}/messages", ['body' => 'Bonjour !'])->assertForbidden();
    }

    public function test_the_two_members_exchange_text_messages(): void
    {
        Notification::fake();
        $request = $this->conversation();
        Sanctum::actingAs($request->sender);

        $this->postJson("/api/contact-requests/{$request->id}/messages", ['body' => 'Merci ! Quand êtes-vous disponible ?'])
            ->assertCreated()
            ->assertJsonPath('data.kind.value', 'texte')
            ->assertJsonPath('data.read_at', null);

        Notification::assertSentTo($request->recipient, NewMessage::class);
        $this->assertSame(1, app(MessageService::class)->unreadCount($request->recipient));

        Sanctum::actingAs($request->recipient);
        $this->getJson("/api/contact-requests/{$request->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Merci ! Quand êtes-vous disponible ?');

        $this->assertSame(0, app(MessageService::class)->unreadCount($request->recipient));
        $this->postJson("/api/contact-requests/{$request->id}/messages", [])->assertUnprocessable()->assertJsonValidationErrors('body');
    }

    public function test_documents_and_voice_notes_stay_private_to_the_conversation(): void
    {
        $request = $this->conversation();
        Sanctum::actingAs($request->recipient);

        $document = $this->post("/api/contact-requests/{$request->id}/messages", [
            'attachment' => UploadedFile::fake()->create('devis.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.kind.value', 'document');

        $this->post("/api/contact-requests/{$request->id}/messages", [
            'attachment' => UploadedFile::fake()->create('vocal.m4a', 300, 'audio/mp4'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.kind.label', 'Message vocal');

        $id = $document->json('data.id');
        $this->get("/api/messages/{$id}/attachment")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        Sanctum::actingAs(User::factory()->create());
        $this->get("/api/messages/{$id}/attachment", ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_unsafe_or_oversized_files_are_refused(): void
    {
        $request = $this->conversation();
        Sanctum::actingAs($request->sender);

        $this->post("/api/contact-requests/{$request->id}/messages", [
            'attachment' => UploadedFile::fake()->create('page.html', 10, 'text/html'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachment');

        $this->post("/api/contact-requests/{$request->id}/messages", [
            'attachment' => UploadedFile::fake()->create('long.m4a', config('idee.messages.max_voice_kb') + 100, 'audio/mp4'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachment');
    }

    public function test_the_conversation_page_shows_and_sends_messages(): void
    {
        $request = $this->conversation();
        app(MessageService::class)->send($request, $request->recipient, 'Je suis disponible jeudi matin.');

        $this->actingAs($request->sender)->get(route('contact-requests.index', ['boite' => 'envoyees']))->assertSee('1 message');

        $this->get(route('contact-requests.show', $request))
            ->assertOk()
            ->assertSee('Je suis disponible jeudi matin.')
            ->assertSee('Message vocal');

        $this->post(route('messages.store', $request), ['body' => 'Parfait, à jeudi.'])
            ->assertRedirect(route('contact-requests.show', $request).'#messages');

        $this->get(route('contact-requests.show', $request))->assertSee('Parfait, à jeudi.');
    }
}
