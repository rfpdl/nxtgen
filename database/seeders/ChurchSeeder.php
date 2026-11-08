<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Support\RouteSatellite;
use App\Foundation\Support\SatelliteCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Production start (D-R2): the church, its satellites (each with its Squads) and ONE first person, the platform admin, from
 * `config('nxtgen.church')` (NXTGEN_* in .env). Safe to run again: it adds what is missing and changes NOTHING
 * that exists — edited settings stay, squads are only ever added, and a removed platform admin stays removed (review 3).
 * They have no password: they sign in by email link and give each satellite its first admin.
 */
final class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        $settings = self::settings();

        DB::transaction(function () use ($settings): void {
            $church = Church::query()->firstOrCreate(['slug' => $settings['slug']], [
                'name' => $settings['name'],
                'timezone' => $settings['timezone'],
                'currency' => 'AED',
                'moving_up_reference_date' => '2027-02-01',
                'settings' => ['satellite_label' => 'Satellite', 'lifeshapers_can_open' => true],
            ]);

            foreach ($settings['satellites'] as $slug => $name) {
                // A code for its QR poster, made once and never changed (spec item 7).
                Squad::createChartFor(Satellite::query()->firstOrCreate(['church_id' => $church->id, 'slug' => $slug], ['name' => $name, 'code' => SatelliteCode::random()]));
            }

            // The first person is the platform admin (Roy 2026-11-08): on no satellite's People, able to open each, and
            // the one who gives each satellite its first admin. Made once: a rerun never puts back what was taken off.
            $admin = User::query()->firstOrCreate(['email' => $settings['first_admin_email']], ['name' => $settings['first_admin_name']]);
            if ($admin->wasRecentlyCreated) {
                $admin->forceFill(['is_platform_admin' => true])->save();
            }
        });
    }

    /**
     * @return array{slug: string, name: string, timezone: string, satellites: array<string, string>, first_admin_email: string, first_admin_name: string}
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

        $satellites = [];
        foreach (array_filter(array_map('trim', explode('|', $text('satellites'))), fn (string $s): bool => $s !== '') as $entry) {
            [$address, $name] = array_map('trim', explode('=', $entry, 2) + [1 => '']);
            $address = mb_strtolower($address);
            if ($name === '' || preg_match('/^'.RouteSatellite::pattern().'$/', $address) !== 1 || SatelliteCode::looksLikeOne($address)) {
                throw new RuntimeException("Satellite \"{$entry}\" needs address=name, and an address the app does not use itself.");
            }
            $satellites[$address] = $name;
        }

        return [
            'slug' => mb_strtolower($text('slug')),
            'name' => $text('name'),
            'timezone' => $text('timezone'),
            'satellites' => $satellites,
            'first_admin_email' => mb_strtolower($text('first_admin_email')),
            'first_admin_name' => $text('first_admin_name'),
        ];
    }

    public static function assign(Church $church, User $user, RoleEnum $role, ScopeTypeEnum $type, int $scopeId): void
    {
        // firstOrCreate: a rerun never switches back on a role the church removed.
        RoleAssignment::query()->firstOrCreate(
            ['church_id' => $church->id, 'user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $scopeId],
            ['is_active' => true],
        );
    }
}
