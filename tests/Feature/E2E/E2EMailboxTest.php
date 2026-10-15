<?php

declare(strict_types=1);

use App\Providers\E2EServiceProvider;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\get;

/*
 * The browser tests sign in the way a person does: by opening the email. Under APP_ENV=e2e only, every sent
 * email is copied to a file the Playwright helpers read (tests/e2e/support/mailbox.ts).
 */

it('is not switched on outside the e2e environment', function (): void {
    expect(app()->getProviders(E2EServiceProvider::class))->toBe([]);
});

it('copies every sent email to the mailbox file when switched on', function (): void {
    $path = E2EServiceProvider::mailboxPath();
    File::delete($path);
    app()->register(E2EServiceProvider::class, force: true);

    Mail::raw('Tap https://example.test/magic/login/abc', fn (Message $m) => $m->to('Pia@Example.com')->subject('Sign in'));

    $lines = array_values(array_filter(explode("\n", (string) File::get($path))));
    $entry = json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR);

    expect($lines)->toHaveCount(1)
        ->and(data_get($entry, 'to'))->toBe(['pia@example.com'])
        ->and(data_get($entry, 'subject'))->toBe('Sign in')
        ->and(data_get($entry, 'body'))->toContain('/magic/login/abc');

    File::delete($path);
});

it('refuses to run against a database that is not an e2e one', function (): void {
    expect(fn () => E2EServiceProvider::assertSafeDatabase('nxtgen'))->toThrow(RuntimeException::class);
    E2EServiceProvider::assertSafeDatabase('nxtgen_e2e');
});

it('answers the health check only when switched on, naming its database', function (): void {
    get('/__e2e/health')->assertNotFound();

    app()->register(E2EServiceProvider::class, force: true);
    app('router')->getRoutes()->refreshNameLookups();

    get('/__e2e/health')->assertOk()->assertJson(['e2e' => true, 'database' => DB::connection()->getDatabaseName()]);
});
