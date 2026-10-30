<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAndCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_is_created_from_five_guided_questions_with_its_first_need(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('projects.create'))
            ->assertSee('2. Quel problème résolvez-vous, et pour qui ?')
            ->assertSee('5. De quoi avez-vous besoin en premier ?')
            // IA-03 : les réponses longues se dictent à la voix.
            ->assertSee('data-dictation', false);

        $this->post(route('projects.store'), [
            'title' => 'Savons au karité',
            'problem' => 'Les femmes de Thiès trouvent difficilement des soins naturels.',
            'solution' => 'Des savons au karité fabriqués localement.',
            'stage' => 'prototype',
            'first_need_type' => 'associe',
            'first_need_description' => 'Un associé commercial avec un réseau de boutiques à Dakar.',
        ])->assertSessionHasNoErrors();

        $project = $user->projects()->firstOrFail();
        $this->assertSame("Les femmes de Thiès trouvent difficilement des soins naturels.\n\nDes savons au karité fabriqués localement.", $project->summary);
        $this->assertSame('associe', $project->needs()->firstOrFail()->type->value);

        $this->post(route('projects.store'), [
            'title' => 'Sans précision',
            'problem' => 'Un problème décrit en assez de mots pour passer.',
            'first_need_type' => 'financement',
        ])->assertSessionHasErrors('first_need_description');
    }

    public function test_a_member_adds_a_light_photo_and_their_availability(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'photo' => UploadedFile::fake()->image('moi.png', 1200, 900),
            'availability' => 'partielle',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->photo_path);
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($user->photo_path));
        $this->assertSame([256, 256], [$width, $height]);

        $this->get(route('members.show', $user))->assertSee('Quelques heures par semaine')->assertSee($user->photoUrl());
        $this->getJson("/api/members/{$user->id}")->assertJsonPath('data.availability.value', 'partielle');
    }
}
