<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Database\Seeder;

/**
 * A local church to click around in: the same church ChurchSeeder makes (CCF, CCF DXB + House Church, NxtGen's
 * groups), with demo staff logins. Sign in with a magic link (emails go to storage/logs/laravel.log) or the staff
 * password "password". NEVER in production — DatabaseSeeder runs ChurchSeeder there.
 */
final class DemoChurchSeeder extends Seeder
{
    public function run(): void
    {
        config(['nxtgen.church' => [
            'slug' => 'ccf',
            'name' => 'CCF',
            'timezone' => 'Asia/Dubai',
            'satellites' => 'CCF DXB|House Church',
            'first_admin_email' => 'admin@nxtgen.test',
            'first_admin_name' => 'Church Admin',
        ]]);
        $this->call(ChurchSeeder::class);

        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        $dxb = Satellite::query()->where('church_id', $ccf->id)->where('slug', 'ccf-dxb')->firstOrFail();

        $admin = User::query()->where('email', 'admin@nxtgen.test')->firstOrFail();
        $admin->forceFill(['email_verified_at' => now(), 'password' => 'password', 'is_platform_admin' => true])->save();

        $lifeshaper = User::query()->updateOrCreate(['email' => 'lifeshaper@nxtgen.test'], ['name' => 'Lifeshaper', 'email_verified_at' => now(), 'password' => 'password']);
        ChurchSeeder::assign($ccf, $lifeshaper, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $dxb->id);
    }
}
