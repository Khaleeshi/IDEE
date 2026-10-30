<?php

namespace Database\Factories;

use App\Enums\NeedStatus;
use App\Enums\NeedType;
use App\Models\Need;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Need>
 */
class NeedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => NeedType::Associe,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(3),
            'status' => NeedStatus::Ouvert,
        ];
    }
}
