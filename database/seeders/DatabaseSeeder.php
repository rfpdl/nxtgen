<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production: the real church from NXTGEN_* settings (ChurchSeeder) and nothing else. Anywhere else: the demo
 * church with its known logins — which must never exist in production (password "password").
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(app()->isProduction() ? ChurchSeeder::class : DemoChurchSeeder::class);
    }
}
