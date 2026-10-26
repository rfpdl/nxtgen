<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Mime\Address;

/**
 * Browser-test support, registered ONLY under APP_ENV=e2e (AppServiceProvider).
 *
 * Sign-in is by email link, so the Playwright journeys open the email the way a person would: every sent
 * message is appended to a JSON-lines file that `tests/e2e/support/mailbox.ts` reads. Nothing here changes
 * what the app does — it only watches what it sends.
 *
 * `/__e2e/health` is what Playwright waits on before running (admin-v2's idea): only an e2e server answers
 * it, so the suite can never start against the dev server on another port, or one pointed at real data.
 *
 * The door's day is the next Saturday (today, on a Saturday): CCF DXB's 1PM and 7PM meet only on Saturdays, and the
 * Lifeshaper journeys check children into 1PM whatever day the suite runs. Only the door reads it
 * (GetChurchTodayJob); every other clock is the real one.
 */
final class E2EServiceProvider extends ServiceProvider
{
    public static function mailboxPath(): string
    {
        return storage_path('app/e2e/mailbox.jsonl');
    }

    /** `migrate:fresh` runs before every browser run. It must never reach the working database. */
    public static function assertSafeDatabase(string $database): void
    {
        if (! Str::endsWith($database, '_e2e')) {
            throw new RuntimeException("APP_ENV=e2e is pointed at [{$database}]; it must be an *_e2e database.");
        }
    }

    /** The e2e church's timezone, as the seeder makes it (config nxtgen.church.timezone). */
    public static function churchTimezone(): string
    {
        $timezone = config('nxtgen.church.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    /** The Saturday the door journeys run on: `$now` if it is one, else the next. */
    public static function doorSaturday(CarbonImmutable $now): string
    {
        return ($now->isSaturday() ? $now : $now->next(CarbonImmutable::SATURDAY))->toDateString();
    }

    public function boot(): void
    {
        if ($this->app->environment('e2e')) {
            self::assertSafeDatabase(DB::connection()->getDatabaseName());
            // Browser journeys run against the built assets `make e2e` just made — never a `make dev` server that
            // happens to be running (its public/hot file would otherwise win, and the journeys get slower dev code).
            Vite::useHotFile(storage_path('framework/e2e-no-hot'));
            config(['nxtgen.door_today' => self::doorSaturday(CarbonImmutable::now(self::churchTimezone()))]);
        }

        Route::get('/__e2e/health', fn () => response()->json([
            'e2e' => true,
            'database' => DB::connection()->getDatabaseName(),
        ]));

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->message;

            File::ensureDirectoryExists(dirname(self::mailboxPath()));
            File::append(self::mailboxPath(), json_encode([
                'to' => array_map(fn (Address $a): string => mb_strtolower($a->getAddress()), $message->getTo()),
                'subject' => $message->getSubject(),
                'body' => (string) ($message->getTextBody() ?? $message->getHtmlBody()),
                'sentAt' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n");
        });
    }
}
