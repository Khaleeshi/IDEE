<?php

namespace Tests\Feature;

use App\Enums\ModerationDecision;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ModerationNotice;
use App\Services\Moderation\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function reportProject(Project $project, string $reason = 'arnaque'): Report
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/reports', [
            'target_type' => 'projet',
            'target_id' => $project->id,
            'reason' => $reason,
            'details' => 'On me demande de payer des frais de dossier.',
        ])->assertCreated()->json('data.id');

        return Report::findOrFail($id);
    }

    public function test_scam_reports_come_first_in_the_queue(): void
    {
        $this->reportProject(Project::factory()->create(), 'spam');
        $scam = $this->reportProject(Project::factory()->create(), 'arnaque');

        $this->assertSame($scam->id, app(ModerationService::class)->queue()->first()->id);
    }

    public function test_a_member_cannot_report_their_own_project_twice_or_at_all(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs($project->owner);

        $this->postJson('/api/reports', ['target_type' => 'projet', 'target_id' => $project->id, 'reason' => 'spam'])
            ->assertUnprocessable()->assertJsonValidationErrors('report');
    }

    public function test_removing_a_project_informs_the_owner_who_can_appeal_once_to_a_second_moderator(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $report = $this->reportProject($project);
        $first = $this->admin();
        $service = app(ModerationService::class);

        $service->decide($report, $first, ModerationDecision::Retrait, 'Frais demandés aux candidats.');
        $this->assertSoftDeleted($project);
        Notification::assertSentTo($project->owner, ModerationNotice::class);

        Sanctum::actingAs($project->owner);
        $this->getJson('/api/me/moderation')->assertJsonPath('data.0.decision.label', 'Retrait du contenu')->assertJsonPath('data.0.can_appeal', true);
        $this->postJson("/api/reports/{$report->id}/appeal", ['appeal' => 'Ces frais sont ceux du notaire, pas les miens.'])->assertOk();
        $this->postJson("/api/reports/{$report->id}/appeal", ['appeal' => 'Je conteste une deuxième fois.'])->assertUnprocessable();

        $this->expectExceptionMessage('Un second modérateur doit examiner le recours.');
        try {
            $service->reviewAppeal($report->fresh(), $first, false);
        } finally {
            $service->reviewAppeal($report->fresh(), $this->admin(), false);
            $this->assertNotSoftDeleted($project);
        }
    }

    public function test_a_suspended_member_is_logged_out_and_cannot_use_the_api(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/reports', ['target_type' => 'membre', 'target_id' => $member->id, 'reason' => 'harcelement'])->json('data.id');

        app(ModerationService::class)->decide(Report::findOrFail($id), $this->admin(), ModerationDecision::Suspension);

        $this->assertTrue($member->fresh()->isRestricted());
        Sanctum::actingAs($member->fresh());
        $this->getJson('/api/me')->assertForbidden();

        $this->actingAs($member->fresh())->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_only_the_team_reaches_the_administration(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();

        $report = $this->reportProject(Project::factory()->create());
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Projets avec réponse utile sous 30 jours');
        $this->get(route('admin.reports'))->assertOk()->assertSee('Arnaque ou demande d&#039;argent', false);
        $this->get(route('admin.reports.show', $report))->assertOk()->assertSee('On me demande de payer des frais de dossier.');
        $this->post(route('admin.reports.decide', $report), ['decision' => 'avertissement', 'note' => 'Restez prudent.'])
            ->assertRedirect(route('admin.reports'));

        $this->assertSame('avertissement', $report->fresh()->decision->value);
    }
}
