<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Console\Features\Platform\SetPlatformAdminFeature;
use Illuminate\Console\Command;
use Lucid\Bus\ServesFeatures;

/**
 * The platform admin (spec item 12) is set here and nowhere else: `php artisan nxtgen:platform-admin roy@example.com`,
 * and `--remove` to take it back.
 */
final class PlatformAdminCommand extends Command
{
    use ServesFeatures;

    protected $signature = 'nxtgen:platform-admin {email : The login to change} {--remove : Take the platform admin back}';

    protected $description = 'Make a login the platform admin (/platform, every satellite), or take it back';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $on = ! (bool) $this->option('remove');
        $user = $this->serve(SetPlatformAdminFeature::class, ['email' => $email, 'isPlatformAdmin' => $on]);

        if ($user === null) {
            $this->error("No login has the email {$email}.");

            return self::FAILURE;
        }

        $this->info($on ? "{$user->email} is now the platform admin." : "{$user->email} is no longer the platform admin.");

        return self::SUCCESS;
    }
}
