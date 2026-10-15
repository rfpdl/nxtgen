<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RoleAssignment> */
final class RoleAssignmentFactory extends Factory
{
    protected $model = RoleAssignment::class;

    /** @return array<model-property<RoleAssignment>, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'role' => RoleEnum::Lifeshaper,
            'scope_type' => ScopeTypeEnum::Organization,
            'scope_id' => fn (array $attributes): mixed => $attributes['organization_id'],
            'is_active' => true,
        ];
    }
}
