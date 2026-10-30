<?php

namespace Tests\Feature\Web;

use App\Models\Journey;
use App\Models\User;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
