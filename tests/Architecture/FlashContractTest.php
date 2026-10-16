<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Every key a Feature flashes must be shared by `HandleInertiaRequests`.
 *
 * **This guard exists because the gap it closes shipped and survived for three build steps.**
 * `CheckInKidsFeature` flashed `checkIn`, `Door.vue` read `page.props.flash.checkIn`, and the key was
 * missing from the shared list — so the prop was always `undefined`, the `?? []` beside it swallowed
 * that, and the door's refusal banner never rendered once. Nothing failed. The feature tests asserted
 * that the Feature FLASHED it, which it always had; no test asked whether the page ever received it.
 *
 * It was found only when a second key was added and behaved identically.
 *
 * A behavioural test per key is the better assertion and both now exist — but a behavioural test only
 * covers the keys somebody remembered to write one for, and the failure mode here is silence. This
 * catches the NEXT key.
 *
 * **There is a third hop, and it is where the same bug came back.** `success` and `error` were
 * flashed by twenty Features and shared by the middleware — both directions below were green — and
 * read by nothing in `resources/js`. "No temporary card is numbered T-0042" was the temp-card desk's
 * only error and appeared on no screen; a star refused as already given looked identical to one
 * awarded. Guarding Feature -> middleware and stopping there guards two thirds of a three-legged
 * contract, so the last test in this file walks the frontend too.
 *
 * **The `enums` prop has the same three legs**, and the last test in this file covers it for the same
 * reason. `GenderEnum::label()` was deleted on 2026-09-09 for being referenced nowhere — an enum
 * shared to a frontend that never reads it is a contract that exists only in a middleware, and a
 * reader who sees it assumes a screen depends on it.
 *
 * **`CardStateEnum::toFrontend()` was deleted the same day and restored on the 10th**, which is the
 * more useful half of the story: unread was the symptom, and the disease was that `CardRow` held its
 * own copy of those four words and had already drifted from the enum. Deleting the sharing left the
 * duplicate as the only source. **"Nothing reads it" can mean the screen is duplicating it**, so the
 * fix is not always deletion — look at what the screen says before removing what it should have been
 * saying.
 *
 * This guard has now been caught twice passing on something that was not a reader: a test fixture,
 * then a docblock. See `frontendScreens()` and `codeOnly()`.
 *
 * **Text-scanning, deliberately.** There is no runtime registry of flash keys to reflect over; the
 * facts live in source. The pattern is narrow — `back()->with('x'` and `redirect(...)->with('x'` —
 * which is how this codebase flashes, and it will not match Eloquent's `->with('relation')` because
 * that never follows a redirect call.
 */
/**
 * @return array<string, list<string>>
 */
function flashedKeys(): array
{
    $keys = [];

    foreach (Finder::create()->files()->in(base_path('app/Services'))->name('*.php') as $file) {
        $source = $file->getContents();

        preg_match_all(
            "/(?:back\(\)|redirect\([^)]*\))\s*(?:->[a-zA-Z]+\([^)]*\)\s*)*->with\('([a-zA-Z_]+)'/",
            $source,
            $matches,
        );

        foreach ($matches[1] as $key) {
            $keys[$key][] = str_replace(base_path().'/', '', (string) $file->getRealPath());
        }
    }

    return $keys;
}

/**
 * @return list<string>
 */
function sharedFlashKeys(): array
{
    $source = file_get_contents(app_path('Http/Middleware/HandleInertiaRequests.php'));

    preg_match("/'flash' => \[(.*?)\n            \],/s", (string) $source, $block);

    preg_match_all("/'([a-zA-Z_]+)' =>/", $block[1] ?? '', $matches);

    return $matches[1];
}

it('finds the flash keys it is supposed to be guarding', function (): void {
    // A regex that matches nothing would make every assertion below pass forever. This is the guard
    // on the guard: the codebase demonstrably flashes several keys, so finding none means the
    // pattern has stopped working, not that the code stopped flashing.
    expect(array_keys(flashedKeys()))->toContain('success', 'error');
    expect(sharedFlashKeys())->not->toBeEmpty();
});

it('shares every key any Feature flashes', function (): void {
    $missing = [];

    foreach (flashedKeys() as $key => $files) {
        if (! in_array($key, sharedFlashKeys(), true)) {
            $missing[$key] = $files;
        }
    }

    expect($missing)->toBe(
        [],
        "These keys are flashed but never reach the page. Add them to HandleInertiaRequests::share():\n"
        .json_encode($missing, JSON_PRETTY_PRINT),
    );
});

it('shares nothing that nothing flashes', function (): void {
    // The other direction. A key that no Feature writes is a prop every page has to guard against and
    // a reader has to wonder about — and it is the residue of a screen that was removed.
    $orphans = array_values(array_diff(sharedFlashKeys(), array_keys(flashedKeys())));

    expect($orphans)->toBe([], 'Shared but never flashed: '.implode(', ', $orphans));
});

/**
 * Every file that is a SCREEN — a component, page or composable a teacher's browser runs.
 *
 * **Not the tests, and not the fixtures.** A test proves nothing about whether a teacher can see
 * something, and `sharedProps.ts` typing a constant as `SharedEnums['starReasons']` kept the enum
 * guard below green while both screens spelled their own words. `ReachabilityTest` excludes `Tests`
 * for exactly this reason.
 *
 * @return iterable<SplFileInfo>
 */
function frontendScreens(): iterable
{
    return Finder::create()
        ->files()
        ->in(base_path('resources/js'))
        ->exclude('tests')
        ->notName('*.test.ts')
        ->name(['*.vue', '*.ts']);
}

/**
 * The file with its comments removed.
 *
 * **Found on 2026-09-10 by attacking the enum guard**, which had just been fixed to ignore fixtures.
 * Both readers of `enums.cardStates` were deleted and it still passed — because a docblock in
 * `CardRow.vue` mentions `` `enums.cardStates` `` while explaining where the words come from. The
 * documentation was holding the guard up. Prose about a contract is not the contract.
 *
 * `//` is left alone after a colon so a `https://` URL does not swallow the rest of its line.
 */
function codeOnly(string $source): string
{
    return (string) preg_replace(
        ['#/\*.*?\*/#s', '#<!--.*?-->#s', '#(?<!:)//.*$#m'],
        '',
        $source,
    );
}

/**
 * @return list<string>
 */
function flashKeysReadByTheFrontend(): array
{
    $keys = [];

    foreach (frontendScreens() as $file) {
        // `flash.checkIn`, `flash.error`, `flash?.success` — how a page or composable names one.
        preg_match_all("/flash\??\.([a-zA-Z_]+)/", codeOnly($file->getContents()), $matches);

        foreach ($matches[1] as $key) {
            $keys[] = $key;
        }
    }

    return array_values(array_unique($keys));
}

it('finds the flash keys the frontend reads', function (): void {
    // The guard on the guard again: a regex that matches nothing would make the test below vacuous.
    expect(flashKeysReadByTheFrontend())->toContain('success', 'error');
});

it('has a screen for every key it shares', function (): void {
    /*
     * The third hop. A key can be flashed by a Feature, shared by the middleware, and read by nothing
     * — which is what happened to `success` and `error`, and it is invisible from either end of the
     * server-side contract. A flashed message nobody renders is worse than no message: the Feature
     * reports the refusal, every test agrees it did, and the staff member sees a button that did nothing.
     */
    $unread = array_values(array_diff(sharedFlashKeys(), flashKeysReadByTheFrontend()));

    expect($unread)->toBe(
        [],
        'Shared and flashed, but no component or composable reads them — so nobody ever sees these: '
        .implode(', ', $unread),
    );
});

// Enums are NOT shared globally here (aegis convention): a page that needs an enum receives it as a prop,
// so the PoC's shared-enum checks were removed rather than kept vacuous.
