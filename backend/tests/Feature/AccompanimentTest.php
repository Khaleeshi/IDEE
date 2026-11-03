<?php

namespace Tests\Feature;

use App\Enums\AccompanimentStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Accompaniment;
use App\Models\MemberProfile;
use App\Models\Profession;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AccompanimentNotice;
use App\Services\Members\MemberProfileService;
use Database\Seeders\ProfessionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Accompagnement bénévole et mentorat (AC-01 à AC-04), avis (AV-01 à AV-03).
 */
class AccompanimentTest extends TestCase
{
    use RefreshDatabase;

    private const OBJECTIVES = 'Je lance une marque de jus locaux à Thiès et j\'ai besoin d\'aide pour fixer mes prix et trouver mes premiers revendeurs.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProfessionSeeder::class);
        Notification::fake();
    }

    /**
     * @param  array<string, mixed>  $offers
     */
    private function mentor(array $offers = ['offers_mentoring' => true, 'offers_volunteer' => true], bool $verified = true): MemberProfile
    {
        $user = ($verified ? User::factory()->identityVerified() : User::factory())->create(['name' => 'Aïssatou Diallo']);
        $user->syncRoles([UserRole::Coach]);

        return app(MemberProfileService::class)->create($user, [
            'profession_id' => Profession::where('slug', 'mentor-entrepreneurial')->value('id'),
            ...$offers,
        ]);
    }

    private function ask(User $requester, MemberProfile $profile, string $kind = 'mentorat'): Accompaniment
    {
        Sanctum::actingAs($requester);
        $id = $this->postJson("/api/member-profiles/{$profile->id}/accompaniments", ['kind' => $kind, 'objectives' => self::OBJECTIVES])
            ->assertCreated()->json('data.id');

        return Accompaniment::findOrFail($id);
    }

    public function test_a_mentorship_goes_from_request_to_review(): void
    {
        $profile = $this->mentor();
        $coach = $profile->user;
        $awa = User::factory()->create(['name' => 'Awa Ndiaye']);

        $accompaniment = $this->ask($awa, $profile);
        Notification::assertSentTo($coach, AccompanimentNotice::class);

        Sanctum::actingAs($coach);
        $this->postJson("/api/accompaniments/{$accompaniment->id}/respond", ['accepted' => true])->assertOk()->assertJsonPath('data.status.value', 'en_cours');
        $this->postJson("/api/accompaniments/{$accompaniment->id}/sessions", [
            'held_on' => today()->toDateString(), 'summary' => 'Prix revus : 1 000 FCFA la bouteille.', 'next_steps' => 'Contacter trois épiceries.',
        ])->assertCreated()->assertJsonPath('data.sessions.0.next_steps', 'Contacter trois épiceries.');

        // Pas d'avis tant que l'accompagnement n'est pas terminé.
        Sanctum::actingAs($awa);
        $this->postJson("/api/accompaniments/{$accompaniment->id}/review", ['rating' => 5])->assertUnprocessable();

        $this->postJson("/api/accompaniments/{$accompaniment->id}/complete")->assertOk()->assertJsonPath('data.status.value', 'terminee');
        $this->postJson("/api/accompaniments/{$accompaniment->id}/review", ['rating' => 5, 'comment' => 'Très concret.'])->assertCreated();
        $this->postJson("/api/accompaniments/{$accompaniment->id}/review", ['rating' => 1])->assertUnprocessable();

        // AV-03 : le coach répond une fois, publiquement.
        $review = Review::firstOrFail();
        Sanctum::actingAs($coach);
        $this->postJson("/api/reviews/{$review->id}/reply", ['reply' => 'Merci Awa, bonne route !'])->assertOk();
        $this->postJson("/api/reviews/{$review->id}/reply", ['reply' => 'Encore'])->assertUnprocessable();

        $this->getJson("/api/members/{$coach->id}/reviews")->assertOk()
            ->assertJsonPath('summary.average', 5)->assertJsonPath('summary.count', 1)
            ->assertJsonPath('data.0.reply', 'Merci Awa, bonne route !');
    }

    public function test_requests_follow_what_the_profile_offers(): void
    {
        $awa = User::factory()->create();

        // Bénévolat seulement : pas de mentorat.
        $volunteer = $this->mentor(['offers_volunteer' => true]);
        Sanctum::actingAs($awa);
        $this->postJson("/api/member-profiles/{$volunteer->id}/accompaniments", ['kind' => 'mentorat', 'objectives' => self::OBJECTIVES])
            ->assertUnprocessable()->assertJsonValidationErrors('kind');

        // Un profil de mentorat d'une identité non vérifiée n'est pas visible : pas de demande.
        $hidden = $this->mentor(verified: false);
        $this->postJson("/api/member-profiles/{$hidden->id}/accompaniments", ['kind' => 'mentorat', 'objectives' => self::OBJECTIVES])
            ->assertUnprocessable()->assertJsonValidationErrors('kind');

        // Objectifs trop courts, puis doublon.
        $profile = $this->mentor();
        $this->postJson("/api/member-profiles/{$profile->id}/accompaniments", ['kind' => 'mentorat', 'objectives' => 'Aidez-moi'])
            ->assertUnprocessable()->assertJsonValidationErrors('objectives');
        $this->ask($awa, $profile);
        $this->postJson("/api/member-profiles/{$profile->id}/accompaniments", ['kind' => 'benevolat', 'objectives' => self::OBJECTIVES])
            ->assertUnprocessable()->assertJsonValidationErrors('objectives');
    }

    public function test_a_mentor_accompanies_three_people_at_most_at_once(): void
    {
        $profile = $this->mentor();
        $requests = collect(range(1, 4))->map(fn () => $this->ask(User::factory()->create(), $profile));

        Sanctum::actingAs($profile->user);
        foreach ($requests->take(3) as $accompaniment) {
            $this->postJson("/api/accompaniments/{$accompaniment->id}/respond", ['accepted' => true])->assertOk();
        }
        $this->postJson("/api/accompaniments/{$requests->last()->id}/respond", ['accepted' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        // Un refus porte un motif.
        $this->postJson("/api/accompaniments/{$requests->last()->id}/respond", ['accepted' => false])->assertUnprocessable();
        $this->postJson("/api/accompaniments/{$requests->last()->id}/respond", ['accepted' => false, 'reason' => 'Complet jusqu\'en janvier.'])
            ->assertOk()->assertJsonPath('data.status.value', 'refusee');
    }

    public function test_only_the_two_people_see_the_accompaniment(): void
    {
        $accompaniment = $this->ask(User::factory()->create(), $this->mentor());

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/accompaniments/{$accompaniment->id}")->assertForbidden();
        $this->postJson("/api/accompaniments/{$accompaniment->id}/respond", ['accepted' => true])->assertForbidden();

        // La personne accompagnée ne peut pas accepter à la place du coach.
        Sanctum::actingAs($accompaniment->requester);
        $this->postJson("/api/accompaniments/{$accompaniment->id}/respond", ['accepted' => true])->assertForbidden();
        $this->postJson("/api/accompaniments/{$accompaniment->id}/cancel")->assertOk()->assertJsonPath('data.status.value', 'annulee');
    }

    public function test_the_whole_accompaniment_works_on_the_website(): void
    {
        $profile = $this->mentor();
        $awa = User::factory()->create(['name' => 'Awa Ndiaye']);

        $this->actingAs($awa)->get(route('members.show', $profile->user))->assertOk()->assertSee('Demander un accompagnement');
        $this->get(route('accompaniments.create', $profile))->assertOk()->assertSee('Mentorat')->assertSee('Aide bénévole');
        $this->post(route('accompaniments.store', $profile), ['kind' => 'benevolat', 'objectives' => self::OBJECTIVES])->assertRedirect();
        $accompaniment = Accompaniment::firstOrFail();

        $this->actingAs($profile->user)->get(route('accompaniments.index'))->assertOk()->assertSee('Awa Ndiaye');
        $this->post(route('accompaniments.respond', $accompaniment), ['accepted' => '1'])->assertRedirect();
        $this->post(route('accompaniments.sessions.store', $accompaniment), ['held_on' => today()->toDateString(), 'summary' => 'Premier échange sur les prix.'])->assertRedirect();
        $this->post(route('accompaniments.complete', $accompaniment))->assertRedirect();

        $this->actingAs($awa)->get(route('accompaniments.show', $accompaniment))->assertOk()->assertSee('Premier échange sur les prix.')->assertSee('Publier mon avis');
        $this->post(route('accompaniments.review', $accompaniment), ['rating' => 4, 'comment' => 'Utile et bienveillant.'])->assertRedirect();

        $this->get(route('members.show', $profile->user))->assertOk()->assertSee('★ 4,0/5')->assertSee('Utile et bienveillant.');
        $this->assertSame(AccompanimentStatus::Terminee, $accompaniment->fresh()->status);
    }

    public function test_a_verified_coach_receives_quotes_and_the_client_reviews_the_service(): void
    {
        $profile = $this->mentor(['offers_paid' => true]);
        $client = User::factory()->create();

        Sanctum::actingAs($client);
        $id = $this->postJson("/api/members/{$profile->user->id}/quotes", ['description' => 'Préparer mon pitch pour un concours en décembre.'])->assertCreated()->json('data.id');

        $quote = QuoteRequest::findOrFail($id);
        $quote->forceFill(['status' => QuoteStatus::Terminee])->save();

        $this->actingAs($client)->post(route('quotes.review', $quote), ['rating' => 5])->assertRedirect();
        $this->assertSame(1, Review::where('reviewee_id', $profile->user->id)->count());
    }

    public function test_accompaniments_and_reviews_are_erased_with_the_account(): void
    {
        $accompaniment = $this->ask($awa = User::factory()->create(), $this->mentor());

        Sanctum::actingAs($awa);
        $this->deleteJson('/api/me', ['confirmation' => 'SUPPRIMER'])->assertSuccessful();

        $this->assertNull(Accompaniment::find($accompaniment->id));
    }
}
