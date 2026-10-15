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
use App\Foundation\Support\RouteOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Production start (D-R2): the church, its satellites, NxtGen's groups and ONE first church admin, from
 * `config('nxtgen.church')` (NXTGEN_* in .env). Safe to run again. The admin has no password: they sign in by
 * email link and add everyone else on People.
 */
final class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        $settings = self::settings();

        DB::transaction(function () use ($settings): void {
            $church = Organization::query()->updateOrCreate(['slug' => $settings['slug']], [
                'name' => $settings['name'],
                'timezone' => $settings['timezone'],
                'currency' => 'AED',
                'moving_up_reference_date' => '2027-02-01',
                'settings' => ['satellite_label' => 'Satellite', 'lifeshapers_can_open' => true],
            ]);

            foreach ($settings['satellites'] as $name) {
                Branch::query()->updateOrCreate(['organization_id' => $church->id, 'slug' => Str::slug($name)], ['name' => $name]);
            }

            self::groups($church);

            $admin = User::query()->firstOrCreate(['email' => $settings['first_admin_email']], ['name' => $settings['first_admin_name']]);
            self::assign($church, $admin, RoleEnum::Admin, ScopeTypeEnum::Organization, $church->id);
        });
    }

    /**
     * @return array{slug: string, name: string, timezone: string, satellites: list<string>, first_admin_email: string, first_admin_name: string}
     */
    private static function settings(): array
    {
        $text = function (string $key): string {
            $value = config("nxtgen.church.{$key}");
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException('NXTGEN_'.mb_strtoupper($key).' is not set — the church cannot be created without it.');
            }

            return trim($value);
        };

        $slug = mb_strtolower($text('slug'));
        if (preg_match('/^'.RouteOrganization::pattern().'$/', $slug) !== 1) {
            throw new RuntimeException("Church address \"{$slug}\" is reserved by the app or not a valid address.");
        }

        $satellites = array_values(array_filter(array_map('trim', explode('|', $text('satellites'))), fn (string $s): bool => $s !== ''));

        return [
            'slug' => $slug,
            'name' => $text('name'),
            'timezone' => $text('timezone'),
            'satellites' => $satellites,
            'first_admin_email' => mb_strtolower($text('first_admin_email')),
            'first_admin_name' => $text('first_admin_name'),
        ];
    }

    /** NxtGen's groups: level 1 mixed (4–5), levels 2–4 split by gender. */
    public static function groups(Organization $org): void
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

    public static function assign(Organization $org, User $user, RoleEnum $role, ScopeTypeEnum $type, int $scopeId): void
    {
        RoleAssignment::query()->updateOrCreate(
            ['user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $scopeId],
            ['organization_id' => $org->id, 'is_active' => true],
        );
    }
}
