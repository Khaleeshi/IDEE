<?php

namespace Database\Factories;

use App\Enums\VerificationLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+2217'.fake()->unique()->numerify('########'),
            'phone_verified_at' => now(),
            'region' => fake()->randomElement(['Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor']),
            'languages' => ['français', 'wolof'],
            'verification_level' => VerificationLevel::Phone,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Membre dont l'identité a été vérifiée.
     */
    public function identityVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_level' => VerificationLevel::Identity,
        ]);
    }
}
