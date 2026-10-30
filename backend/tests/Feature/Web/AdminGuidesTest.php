<?php

namespace Tests\Feature\Web;

use App\Models\Journey;
use App\Models\User;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGuidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_team_corrects_a_guide_and_the_correction_survives_a_new_release(): void
    {
        $this->seed(JourneySeeder::class);
        $step = Journey::where('slug', 'exporter')->firstOrFail()->steps()->where('slug', 'carte-import-export')->firstOrFail();
        $this->travel(config('idee.guide_review_after_months') + 1)->months();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.guides'))->assertOk()->assertSee('À revoir');

        $this->put(route('admin.guides.update', $step), [
            'title' => $step->title,
            'body' => $step->body,
            'documents' => "Photocopie du NINEA\nTimbre fiscal",
            'cost' => '45 000 FCFA',
            'source_url' => $step->source_url,
            'source_label' => $step->source_label,
            'checked' => 1,
        ])->assertRedirect(route('admin.guides'));

        $step->refresh();
        $this->assertFalse($step->isStale());
        $this->assertSame(['Photocopie du NINEA', 'Timbre fiscal'], $step->documents);

        $this->seed(JourneySeeder::class);
        $this->assertSame('45 000 FCFA', $step->fresh()->cost);
    }

    public function test_the_team_adds_wolof_text_and_audio_to_a_guide(): void
    {
        Storage::fake('public');
        $this->seed(JourneySeeder::class);
        $journey = Journey::where('slug', 'exporter')->firstOrFail();
        $step = $journey->steps()->where('slug', 'carte-import-export')->firstOrFail();

        $this->get(route('journeys.show', $journey))->assertDontSee('Wolof');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.guides.edit', $step))->assertSee('En wolof');
        $this->put(route('admin.guides.update', $step), [
            'title' => $step->title,
            'body' => $step->body,
            'title_wo' => 'Kart import-export',
            'body_wo' => 'Texte wolof de la fiche, relu par l\'équipe.',
            'audio' => UploadedFile::fake()->create('fiche.mp3', 300, 'audio/mpeg'),
        ])->assertRedirect(route('admin.guides'));

        $step->refresh();
        Storage::disk('public')->assertExists($step->audio_path);

        $this->get(route('journeys.show', [$journey, 'langue' => 'wo']))
            ->assertOk()
            ->assertSee('Kart import-export')
            ->assertSee('Texte wolof de la fiche')
            ->assertSee('Écouter en wolof')
            ->assertSee('pas encore traduite en wolof');
        $this->get(route('journeys.show', $journey))->assertSee('Wolof')->assertSee($step->title);
        $this->getJson("/api/journeys/{$journey->id}")->assertJsonFragment(['title' => 'Kart import-export']);

        $this->put(route('admin.guides.update', $step), ['title' => $step->title, 'body' => $step->body, 'remove_audio' => 1]);
        $this->assertNull($step->fresh()->audio_path);
    }
}
