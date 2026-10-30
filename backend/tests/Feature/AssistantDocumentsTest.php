<?php

namespace Tests\Feature;

use App\Contracts\Assistant;
use App\Enums\ContactRequestStatus;
use App\Enums\MeetingMode;
use App\Models\AssistantDocument;
use App\Models\ContactRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\Assistant\AssistantReply;
use App\Services\Collaboration\MeetingService;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantDocumentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    public array $prompts = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['idee.ai.enabled' => true]);
        $test = $this;

        $this->app->instance(Assistant::class, new class($test) implements Assistant
        {
            public function __construct(private AssistantDocumentsTest $test) {}

            public function ask(string $instructions, string $content, array $schema, string $effort): AssistantReply
            {
                $this->test->prompts[] = $instructions.$content;

                $data = isset($schema['properties']['objective'])
                    ? [
                        'objective' => 'Convenir d\'un premier lot de séchoirs.',
                        'questions' => ['Quel délai de fabrication ?', 'Quelle garantie ?'],
                        'talking_points' => ['Volumes de mangue par saison'],
                        'to_bring' => ['Devis du transport'],
                        'next_steps' => ['Visite de l\'atelier'],
                    ]
                    : [
                        'tagline' => 'La mangue de Casamance, séchée au soleil.',
                        'slides' => [
                            ['key' => 'probleme', 'title' => 'Des mangues perdues', 'bullets' => ['40 % des mangues pourrissent', 'à compléter'], 'notes' => 'Chaque saison...'],
                            ['key' => 'besoins', 'title' => 'Ce que nous cherchons', 'bullets' => ['Un séchoir de 200 kg'], 'notes' => ''],
                        ],
                    ];

                return new AssistantReply($data, 'modele-de-test', 500, 700);
            }
        });
    }

    private function conversation(): ContactRequest
    {
        $project = Project::factory()->create(['details' => 'Marge secrète de 45 %']);
        $service = app(ContactRequestService::class);
        $request = $service->send(User::factory()->create(), $project->owner, str_repeat('Bonjour, je fabrique des séchoirs solaires et votre projet m\'intéresse. ', 2), $project);
        $service->respond($request, $project->owner, ContactRequestStatus::Interesse);

        return $request->fresh();
    }

    public function test_the_assistant_prepares_a_meeting_without_revealing_private_details(): void
    {
        $conversation = $this->conversation();
        $meeting = app(MeetingService::class)->propose($conversation, $conversation->recipient, now()->addDays(2), MeetingMode::Visio, null, 'Volumes et prix');

        // Le partenaire, qui n'est pas le porteur, prépare le rendez-vous.
        Sanctum::actingAs($conversation->sender);
        $documentId = $this->postJson("/api/meetings/{$meeting->id}/preparation")
            ->assertAccepted()
            ->json('data.id');

        $this->getJson("/api/assistant-documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'pret')
            ->assertJsonPath('data.content.objective', 'Convenir d\'un premier lot de séchoirs.');
        $this->assertStringNotContainsString('Marge secrète', $this->prompts[0]);
        $this->assertStringContainsString('Volumes et prix', $this->prompts[0]);
        $this->assertStringNotContainsString((string) $conversation->recipient->phone, $this->prompts[0]);

        // Une préparation reste personnelle.
        Sanctum::actingAs($conversation->recipient);
        $this->getJson("/api/assistant-documents/{$documentId}")->assertNotFound();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/meetings/{$meeting->id}/preparation")->assertForbidden();

        $this->actingAs($conversation->sender)
            ->get(route('assistant.document', $documentId))
            ->assertOk()
            ->assertSee('Questions à poser')
            ->assertSee('Quel délai de fabrication ?');
        $this->get(route('contact-requests.show', $conversation))->assertSee('Préparer ce rendez-vous avec l');
    }

    public function test_meeting_preparations_are_capped_per_day(): void
    {
        config(['idee.ai.meeting_preparations_per_day' => 1]);
        $conversation = $this->conversation();
        $meeting = app(MeetingService::class)->propose($conversation, $conversation->recipient, now()->addDays(2), MeetingMode::Visio, null, null);
        Sanctum::actingAs($conversation->sender);

        $this->postJson("/api/meetings/{$meeting->id}/preparation")->assertAccepted();
        $this->postJson("/api/meetings/{$meeting->id}/preparation")->assertUnprocessable()->assertJsonValidationErrors('assistant');
    }

    public function test_the_assistant_drafts_an_editable_and_printable_pitch_deck(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs($project->owner);

        $deckId = $this->postJson("/api/projects/{$project->id}/pitch-deck")->assertAccepted()->json('data.id');

        $this->getJson("/api/projects/{$project->id}/pitch-deck")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'pret')
            ->assertJsonPath('data.content.tagline', 'La mangue de Casamance, séchée au soleil.')
            ->assertJsonCount(9, 'data.content.slides')
            ->assertJsonPath('data.content.slides.0.title', 'Des mangues perdues')
            ->assertJsonPath('data.content.slides.1.title', 'Notre solution');
        $this->assertStringContainsString('ne constitue jamais une offre au public', $this->prompts[0]);

        $this->putJson("/api/pitch-decks/{$deckId}", ['slides' => ['probleme' => ['title' => 'Des mangues gâchées', 'bullets' => "30 % des mangues pourrissent\nFaute de séchoir", 'notes' => '']]])
            ->assertOk()
            ->assertJsonPath('data.content.slides.0.bullets', ['30 % des mangues pourrissent', 'Faute de séchoir']);

        $this->actingAs($project->owner)->get(route('pitch-decks.show', $project))->assertOk()->assertSee('Des mangues gâchées');
        $this->get(route('pitch-decks.print', $project))->assertOk()->assertSee('Faute de séchoir')->assertSee('ni une offre de titres au public');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$project->id}/pitch-deck")->assertForbidden();
        $this->assertSame(1, AssistantDocument::count());
    }

    public function test_pitch_decks_stay_off_until_the_assistant_is_enabled(): void
    {
        config(['idee.ai.enabled' => false]);
        $project = Project::factory()->create();

        $this->actingAs($project->owner)->get(route('pitch-decks.show', $project))->assertSee('arrive bientôt');
        $this->post(route('pitch-decks.generate', $project))->assertSessionHasErrors('assistant');
    }
}
