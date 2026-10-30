<?php

namespace Tests\Feature;

use App\Contracts\Assistant;
use App\Exceptions\AssistantUnavailable;
use App\Models\BusinessPlan;
use App\Models\IdeaClarification;
use App\Models\Project;
use App\Services\Assistant\AssistantReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $prompts = [];

    private function fakeAssistant(?string $failure = null): void
    {
        config(['idee.ai.enabled' => true]);
        $test = $this;

        $this->app->instance(Assistant::class, new class($test, $failure) implements Assistant
        {
            public function __construct(private AssistantTest $test, private ?string $failure) {}

            public function ask(string $instructions, string $content, array $schema, string $effort): AssistantReply
            {
                $this->test->record($instructions.$content);

                if ($this->failure !== null) {
                    throw new AssistantUnavailable($this->failure);
                }

                $data = isset($schema['properties']['questions'])
                    ? ['questions' => ['Qui sont vos premiers clients ?', 'À quel prix vendez-vous ?'], 'suggestions' => ['Testez un petit lot.']]
                    : ['sections' => [
                        ['key' => 'resume', 'content' => 'Une marque de savons au karité fabriqués à Thiès.'],
                        ['key' => 'financement', 'content' => 'Deux millions FCFA pour le premier lot.'],
                    ], 'assumptions' => [['label' => 'Prix du savon', 'value' => '1 500 FCFA', 'basis' => 'Prix relevés au marché de Thiès']]];

                return new AssistantReply($data, 'claude-opus-5-5', 900, 1200);
            }
        });
    }

    public function record(string $prompt): void
    {
        $this->prompts[] = $prompt;
    }

    public function test_the_assistant_stays_off_until_it_is_enabled(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('arrive bientôt sur IDEE');
        $this->post(route('business-plans.generate', $project))->assertSessionHasErrors('assistant');
    }

    public function test_the_assistant_asks_questions_then_drafts_an_editable_business_plan(): void
    {
        $this->fakeAssistant();
        $project = Project::factory()->create(['title' => 'Savons au karité']);
        $this->actingAs($project->owner);

        $this->post(route('assistant.clarify', $project))->assertRedirect(route('projects.show', $project).'#assistant');
        $this->get(route('projects.show', $project))->assertSee('Qui sont vos premiers clients ?')->assertSee('Testez un petit lot.');

        $clarification = IdeaClarification::firstOrFail();
        $this->put(route('assistant.answer', $clarification), ['answers' => ['Les salons de beauté de Thiès', '1 500 FCFA']]);

        $this->post(route('business-plans.generate', $project))->assertRedirect(route('business-plans.show', $project));
        $this->assertStringContainsString('Réponse du porteur : Les salons de beauté de Thiès', end($this->prompts));
        $this->assertStringContainsString('Ne promets jamais un financement', end($this->prompts));

        $this->get(route('business-plans.show', $project))
            ->assertSee('Une marque de savons au karité fabriqués à Thiès.')
            ->assertSee('Besoins et utilisation des fonds')
            ->assertSee('Prix relevés au marché de Thiès');

        $plan = BusinessPlan::firstOrFail();
        $this->put(route('business-plans.update', $plan), ['sections' => ['resume' => 'Savons artisanaux au karité, vendus en salon.']]);
        $this->assertSame('Savons artisanaux au karité, vendus en salon.', $plan->fresh()->sections[0]['content']);
        $this->assertSame(1200, $plan->fresh()->output_tokens);

        $this->get(route('business-plans.print', $project))->assertOk()->assertSee('ni une offre de titres ni une promesse de rendement');
    }

    public function test_generations_are_capped_and_failures_are_explained(): void
    {
        $this->fakeAssistant('L\'assistant est injoignable pour le moment. Réessayez plus tard.');
        config(['idee.ai.business_plans_per_month' => 1]);
        $project = Project::factory()->create();
        Sanctum::actingAs($project->owner);

        $this->postJson("/api/projects/{$project->id}/business-plan")->assertAccepted();
        $this->getJson("/api/projects/{$project->id}/business-plan")
            ->assertJsonPath('data.status', 'echec')
            ->assertJsonPath('data.error', 'L\'assistant est injoignable pour le moment. Réessayez plus tard.');

        $this->postJson("/api/projects/{$project->id}/business-plan")->assertUnprocessable()->assertJsonValidationErrors('assistant');
    }
}
