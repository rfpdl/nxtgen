<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<model-property<User>, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function withPassword(string $password = 'password'): static
    {
        return $this->state(['password' => $password]);
    }

    public function platformAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['is_platform_admin' => true])->save();
        });
    }
}
