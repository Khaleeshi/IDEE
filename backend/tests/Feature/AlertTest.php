<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Models\Need;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\ApplicationDeadline;
use App\Notifications\SavedSearchMatches;
use App\Notifications\WeeklyDigest;
use App\Services\ContactRequests\ContactRequestService;
use App\Services\Opportunities\OpportunityService;
use Database\Seeders\OpportunitySeeder;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectorSeeder::class, OpportunitySeeder::class]);
    }

    public function test_a_saved_search_alerts_on_new_matching_projects_once(): void
    {
        Notification::fake();
        $member = User::factory()->create();
        $sector = Sector::firstWhere('slug', 'agro-transformation');
        Sanctum::actingAs($member);

        $this->postJson('/api/me/alerts', [])->assertUnprocessable()->assertJsonValidationErrors('filters');

        $alertId = $this->postJson('/api/me/alerts', ['sector' => $sector->slug, 'region' => 'Thiès', 'page' => '3'])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Agro-transformation · Thiès')
            ->assertJsonPath('data.filters', ['sector' => 'agro-transformation', 'region' => 'Thiès'])
            ->json('data.id');

        // La même recherche n'est pas enregistrée deux fois.
        $this->postJson('/api/me/alerts', ['region' => 'Thiès', 'sector' => $sector->slug])->assertOk()->assertJsonPath('data.id', $alertId);

        $this->travel(1)->hours();
        Project::factory()->create(['sector_id' => $sector->id, 'region' => 'Thiès']);
        Project::factory()->create(['sector_id' => $sector->id, 'region' => 'Dakar']);
        Project::factory()->for($member, 'owner')->create(['sector_id' => $sector->id, 'region' => 'Thiès']);

        $this->artisan('idee:alerts')->expectsOutputToContain('1 alerte(s)')->assertSuccessful();
        Notification::assertSentTo($member, SavedSearchMatches::class, fn (SavedSearchMatches $notification) => $notification->count === 1);

        $this->artisan('idee:alerts')->expectsOutputToContain('0 alerte(s)');

        $this->deleteJson("/api/alerts/{$alertId}")->assertNoContent();
        $this->getJson('/api/me/alerts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_number_of_alerts_is_capped(): void
    {
        config(['idee.alerts.max_saved_searches' => 1]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/me/alerts', ['q' => 'mangue'])->assertCreated();
        $this->postJson('/api/me/alerts', ['q' => 'karité'])->assertUnprocessable()->assertJsonValidationErrors('filters');
    }

    public function test_alerts_are_created_from_the_project_search_page(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);

        $this->get(route('home', ['q' => 'séchoir']))->assertSee("M'alerter des nouveaux projets", false);
        $this->get(route('home', ['vue' => 'projets']))->assertDontSee("M'alerter des nouveaux projets", false);

        $this->post(route('alerts.store'), ['q' => 'séchoir'])->assertRedirect(route('home', ['vue' => 'projets', 'q' => 'séchoir']));
        $alert = $member->savedSearches()->sole();

        $this->get(route('alerts.index'))->assertOk()->assertSee('« séchoir »', false);
        $this->get(route('alerts.show', $alert))->assertRedirect($alert->url());
        $this->actingAs(User::factory()->create())->get(route('alerts.show', $alert))->assertNotFound();
    }

    public function test_opportunities_are_filtered_by_eligibility_for_a_project(): void
    {
        $owner = User::factory()->create();
        $idea = Project::factory()->for($owner, 'owner')->create(['stage' => ProjectStage::Idee]);
        $running = Project::factory()->for($owner, 'owner')->create(['stage' => ProjectStage::Lancement]);
        Need::factory()->for($running)->create(['type' => NeedType::Financement, 'amount_fcfa' => 35_000_000]);
        Sanctum::actingAs($owner);

        $forIdea = collect($this->getJson("/api/projects/{$idea->id}/opportunities")->assertOk()->json('data'))->pluck('id');
        $forRunning = collect($this->getJson("/api/projects/{$running->id}/opportunities")->assertOk()->json('data'))->pluck('id');

        $guarantee = Opportunity::firstWhere('slug', 'fongip-garantie-individuelle');
        $portfolio = Opportunity::firstWhere('slug', 'fongip-garantie-portefeuille');
        $this->assertFalse($forIdea->contains($guarantee->id), 'Réservée aux entreprises en activité.');
        $this->assertTrue($forRunning->contains($guarantee->id));
        $this->assertFalse($forRunning->contains($portfolio->id), '35 millions dépassent le plafond de 20 millions.');
        $this->assertTrue($forIdea->contains($portfolio->id), 'Sans montant renseigné, le plafond ne bloque pas.');

        $this->getJson("/api/projects/{$running->id}/opportunities")
            ->assertJsonPath('data.0.eligibility.eligible', true);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$running->id}/opportunities")->assertForbidden();

        $this->actingAs($owner)
            ->get(route('opportunities.index', ['projet' => $idea->id]))
            ->assertOk()
            ->assertSee('Compatibles avec mon projet')
            ->assertDontSee($guarantee->title);

        $this->get(route('opportunities.show', $guarantee))
            ->assertSee('Pour mes projets')
            ->assertSee('non compatible');
    }

    public function test_members_are_warned_before_the_deadline_of_an_application_in_preparation(): void
    {
        Notification::fake();
        $member = User::factory()->create();
        $opportunity = Opportunity::firstWhere('slug', 'der-fj-financement');
        $opportunity->update(['deadline' => today()->addDays(5)]);
        $submitted = Opportunity::firstWhere('slug', 'teranga-capital');
        $submitted->update(['deadline' => today()->addDays(5)]);
        $service = app(OpportunityService::class);
        $service->track($member, $opportunity, ApplicationStatus::EnPreparation);
        $service->track($member, $submitted, ApplicationStatus::Deposee);

        $this->assertSame(1, $service->sendDeadlineAlerts());
        Notification::assertSentTo($member, ApplicationDeadline::class, fn (ApplicationDeadline $notification) => $notification->daysLeft === 5);
        $this->assertSame(0, $service->sendDeadlineAlerts());

        $this->travelTo(today()->addDays(4));
        $this->assertSame(1, $service->sendDeadlineAlerts());
        Notification::assertSentToTimes($member, ApplicationDeadline::class, 2);
        $this->assertSame(0, $service->sendDeadlineAlerts());
    }

    public function test_the_weekly_digest_goes_to_confirmed_addresses_with_something_to_say(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['email' => 'awa@example.sn', 'email_verified_at' => now()]);
        $project = Project::factory()->for($owner, 'owner')->create(['stage' => ProjectStage::Lancement]);
        $unconfirmed = User::factory()->create(['email' => 'moussa@example.sn', 'email_verified_at' => null]);
        $optedOut = User::factory()->create(['email' => 'fatou@example.sn', 'email_verified_at' => now(), 'contact_preferences' => ['weekly_digest' => false]]);
        $quiet = User::factory()->create(['email' => 'ibou@example.sn', 'email_verified_at' => now()]);

        app(ContactRequestService::class)->send(User::factory()->create(), $owner, str_repeat('Bonjour, votre projet de transformation m\'intéresse beaucoup. ', 3), $project);
        Project::factory()->for($unconfirmed, 'owner')->create();
        Project::factory()->for($optedOut, 'owner')->create();
        Opportunity::firstWhere('slug', 'fongip-garantie-individuelle')->update(['deadline' => today()->addDays(10)]);

        $this->artisan('idee:weekly-digest')->expectsOutputToContain('1 résumé(s)')->assertSuccessful();

        Notification::assertSentTo($owner, WeeklyDigest::class, function (WeeklyDigest $digest) use ($owner) {
            $mail = $digest->toMail($owner);
            $lines = implode("\n", $mail->introLines);

            return $digest->summary['pending_requests'] === 1
                && str_contains($lines, '1 demande attend votre réponse')
                && str_contains($lines, 'Garantie individuelle de crédit');
        });
        Notification::assertNotSentTo([$unconfirmed, $optedOut, $quiet], WeeklyDigest::class);
    }

    public function test_the_weekly_digest_can_be_turned_off_in_the_profile(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($member)->get(route('profile.edit'))->assertSee('Recevoir le résumé de la semaine par e-mail');
        Sanctum::actingAs($member);
        $this->patchJson('/api/me', ['contact_preferences' => ['accepts_requests' => true, 'weekly_digest' => false]])->assertOk();

        $this->assertFalse($member->fresh()->contact_preferences['weekly_digest']);
    }
}
