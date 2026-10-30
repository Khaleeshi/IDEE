<?php

namespace Database\Factories;

use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(4),
            'details' => fake()->paragraphs(3, true),
            'region' => 'Dakar',
            'stage' => ProjectStage::Validation,
            'status' => ProjectStatus::Actif,
            'last_activity_at' => now(),
        ];
    }
}
