<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Branch;
use App\Data\Models\Group;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Database\Seeder;

/**
 * A local church to click around in: CCF with two satellites, NxtGen's groups, and two staff logins.
 * Sign in with a magic link (emails go to storage/logs/laravel.log) or the staff password "password".
 * Real families come from the registry import, never from here.
 */
final class DemoChurchSeeder extends Seeder
{
    public function run(): void
    {
        $ccf = Organization::query()->updateOrCreate(['slug' => 'ccf'], [
            'name' => 'CCF',
            'timezone' => 'Asia/Dubai',
            'currency' => 'AED',
            'moving_up_reference_date' => '2027-02-01',
            'settings' => ['satellite_label' => 'Satellite', 'lifeshapers_can_open' => true],
        ]);

        $dxb = Branch::query()->updateOrCreate(['organization_id' => $ccf->id, 'slug' => 'ccf-dxb'], ['name' => 'CCF DXB']);
        Branch::query()->updateOrCreate(['organization_id' => $ccf->id, 'slug' => 'house-church'], ['name' => 'House Church']);

        $this->groups($ccf);

        $admin = User::query()->updateOrCreate(['email' => 'admin@nxtgen.test'], ['name' => 'Church Admin', 'email_verified_at' => now(), 'password' => 'password']);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->assign($ccf, $admin, RoleEnum::Admin, ScopeTypeEnum::Organization, $ccf->id);

        $lifeshaper = User::query()->updateOrCreate(['email' => 'lifeshaper@nxtgen.test'], ['name' => 'Lifeshaper', 'email_verified_at' => now(), 'password' => 'password']);
        $this->assign($ccf, $lifeshaper, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $dxb->id);
    }

    /** NxtGen's groups: level 1 mixed (4–5), levels 2–4 split by gender. */
    private function groups(Organization $org): void
    {
        $levels = [[2, 6, 7], [3, 8, 9], [4, 10, 12]];
        $previous = [];
        $top = [];

        foreach (array_reverse($levels) as [$level, $min, $max]) {
            foreach ([GenderEnum::Boy, GenderEnum::Girl] as $gender) {
                $name = "Level {$level} ".($gender === GenderEnum::Boy ? 'Boys' : 'Girls');
                $group = Group::query()->updateOrCreate(['organization_id' => $org->id, 'name' => $name], [
                    'sort' => $level * 10 + ($gender === GenderEnum::Boy ? 0 : 1),
                    'min_age' => $min,
                    'max_age' => $max,
                    'gender' => $gender,
                    'next_group_id' => $previous[$gender->value] ?? null,
                ]);
                $top[$gender->value] = $group->id;
            }
            $previous = $top;
        }

        Group::query()->updateOrCreate(['organization_id' => $org->id, 'name' => 'Level 1'], [
            'sort' => 10, 'min_age' => 4, 'max_age' => 5, 'gender' => null, 'next_group_id' => null,
        ]);
    }

    private function assign(Organization $org, User $user, RoleEnum $role, ScopeTypeEnum $type, int $scopeId): void
    {
        RoleAssignment::query()->updateOrCreate(
            ['user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $scopeId],
            ['organization_id' => $org->id, 'is_active' => true],
        );
    }
}
