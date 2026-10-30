<?php

namespace Tests\Feature;

use App\Enums\AssistantStatus;
use App\Models\BusinessPlan;
use App\Models\Project;
use App\Models\User;
use App\Services\Assistant\BusinessPlanDocument;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use ZipArchive;

class BusinessPlanWordTest extends TestCase
{
    use RefreshDatabase;

    private function plan(Project $project): BusinessPlan
    {
        return BusinessPlan::forceCreate([
            'project_id' => $project->id,
            'version' => 2,
            'status' => AssistantStatus::Pret,
            'generated_at' => now(),
            'sections' => [
                ['key' => 'resume', 'title' => 'Résumé', 'content' => "Savons & beurre de karité <bio>.\nVendus à Thiès d'abord.\u{0007}"],
                ['key' => 'marche', 'title' => 'Marché', 'content' => ''],
            ],
            'assumptions' => [['label' => 'Prix de vente', 'value' => '1 500 FCFA', 'basis' => 'Prix du marché de Thiès']],
        ]);
    }

    /**
     * Ouvre le fichier .docx reçu et renvoie le texte de son document principal, vérifié comme XML valide.
     */
    private function documentXml(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test-docx-');
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Le fichier est une archive .docx.');

        foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml', 'word/styles.xml', 'docProps/core.xml'] as $part) {
            $xml = $zip->getFromName($part);
            $this->assertIsString($xml, "Partie manquante : {$part}");
            $this->assertTrue((new DOMDocument)->loadXML($xml), "XML invalide : {$part}");
        }

        $document = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        return $document;
    }

    public function test_the_owner_downloads_the_business_plan_in_word(): void
    {
        $project = Project::factory()->create(['title' => 'Savons au karité de Thiès']);
        $this->plan($project);

        $response = $this->actingAs($project->owner)->get(route('business-plans.word', $project))
            ->assertOk()
            ->assertHeader('Content-Type', BusinessPlanDocument::MIME)
            ->assertHeader('Content-Disposition', 'attachment; filename="business-plan-savons-au-karite-de-thies.docx"');

        $xml = $this->documentXml($response->getContent());
        $this->assertStringContainsString('Savons &amp; beurre de karité &lt;bio&gt;.', $xml);
        $this->assertStringContainsString('Vendus à Thiès d&apos;abord.', $xml);
        $this->assertStringNotContainsString('Marché', $xml, 'Une section vide est omise.');
        $this->assertStringContainsString('Prix du marché de Thiès', $xml);
        $this->assertStringContainsString('Généré avec l&apos;IA, à vérifier', $xml);

        $this->get(route('business-plans.show', $project))->assertSee('Télécharger en Word');
    }

    public function test_only_the_owner_gets_the_file(): void
    {
        $project = Project::factory()->create();
        $this->plan($project);

        $this->actingAs(User::factory()->create())->get(route('business-plans.word', $project))->assertForbidden();

        Sanctum::actingAs($project->owner);
        $this->get("/api/projects/{$project->id}/business-plan/word")->assertOk()->assertHeader('Content-Type', BusinessPlanDocument::MIME);
    }
}
