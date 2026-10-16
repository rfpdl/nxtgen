<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Production: the real church from NXTGEN_* settings (ChurchSeeder) and nothing else. Anywhere else: the demo
 * church with its known logins — which must never exist in production (password "password").
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isProduction()) {
            $this->call(DemoChurchSeeder::class);

            return;
        }

        // The first admin signs in by email link; with a log/array mailer it would never arrive (review 3, B2).
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            throw new RuntimeException("MAIL_MAILER is \"{$mailer}\": production emails would never be sent, so the first admin could not sign in. Set a real mailer first.");
        }

        $this->call(ChurchSeeder::class);
    }
}
