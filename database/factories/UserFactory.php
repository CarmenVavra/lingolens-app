<?php

namespace Database\Factories;

use App\Enums\SchoolType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'school_type' => SchoolType::BHS,
            'school_form' => 'HTL',
            'department' => 'Chemieingenieurwesen',
            'class_name' => '5CHC',
            'grade' => 5,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function ahs(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_type' => SchoolType::AHS,
            'school_form' => 'AHS',
            'department' => null,
            'class_name' => '8A',
            'grade' => 8,
        ]);
    }
}
