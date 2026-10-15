<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Nothing in the application is unreachable.
 *
 * Adapted from palo's `ReachabilityArchTest`, which exists for a reason this project has already met
 * twice: code that nothing calls looks finished, passes every gate, and is discovered months later by
 * somebody trying to change it.
 *
 * Dead code here is not merely untidy. A Job nobody runs has never been executed against a real
 * schema, so its first caller inherits an unproven unit that reviewed as if it worked.
 */

/**
 * @return list<string>
 */
function classNames(string $directory, string $suffix): array
{
    if (! is_dir(base_path($directory))) {
        return [];
    }

    $names = [];

    foreach (Finder::create()->files()->in(base_path($directory))->name("*{$suffix}.php") as $file) {
        $names[] = $file->getFilenameWithoutExtension();
    }

    sort($names);

    return array_values(array_unique($names));
}

/** Everything under `app/`, as one haystack. Cheap enough at this size, and impossible to get wrong. */
function appSource(): string
{
    /** @var string|null $source */
    static $source = null;

    if ($source === null) {
        $source = '';

        /*
         * **`exclude('Tests')`, and a colocated test is what found the need for it.**
         *
         * `phpunit.xml` declares the domain suites at `app/Domains/*` `/Tests`, so the Jobs' own unit
         * tests live INSIDE `app`. Without this exclusion, writing a test for a Job made that Job
         * look like it had a production caller — and the honesty check below immediately demanded
         * that seven Jobs be removed from the waiting list on the strength of nothing but their new
         * tests. Which would have been the opposite of the truth: they are still unreachable from any
         * screen, and that is exactly what the list is for.
         *
         * A test is not a caller. It proves the unit works; it does not prove anybody can get to it.
         */
        $files = Finder::create()
            ->files()
            ->in(base_path('app'))
            ->exclude('Tests')
            ->name('*.php');

        foreach ($files as $file) {
            $source .= $file->getContents();
        }
    }

    return $source;
}

/**
 * Jobs written before the screen that will run them, with the step that will.
 *
 * **An allow-list, not an exemption.** Writing a Job ahead of its caller is a reasonable order to
 * work in — the domain is clearer than the screen — but a Job nothing runs has never touched a real
 * schema, so it is unproven code that reviewed as though it worked. Naming the step is the promise
 * that it will not stay that way, and the test below deletes entries by failing when they are kept
 * past their usefulness.
 *
 * Every one of these dates from step 2, when the domains were laid out ahead of the screens.
 */
/**
 * Jobs allowed to exist before their caller does. Empty. Add a Job here only with the screen that will
 * call it; the list must shrink, never rot.
 *
 * @return array<string, string>
 */
function waitingForACaller(): array
{
    return [];
}

it('finds the units it is supposed to be guarding', function (): void {
    // The guard on the guard: an empty list would make every assertion below pass forever.
    expect(classNames('app/Domains', 'Job'))->not->toBeEmpty();
    expect(classNames('app/Services', 'Feature'))->not->toBeEmpty();
});

/**
 * A Feature nobody serves is a screen or an action that cannot be reached — the work is done and the
 * user cannot get to it.
 */
it('serves every Feature from a controller', function (): void {
    $unreachable = [];

    foreach (classNames('app/Services', 'Feature') as $feature) {
        // The base classes are inherited, never served.
        if (str_starts_with($feature, 'Base')) {
            continue;
        }

        if (! str_contains(appSource(), "{$feature}::class")) {
            $unreachable[] = $feature;
        }
    }

    expect($unreachable)->toBe([], 'Features no controller serves: '.implode(', ', $unreachable));
});

/**
 * A Job nobody runs has never touched a real schema. Its first caller inherits an unproven unit that
 * reviewed as though it worked.
 */
it('runs every Job from somewhere', function (): void {
    $unused = [];

    foreach (classNames('app/Domains', 'Job') as $job) {
        if (str_starts_with($job, 'Base') || array_key_exists($job, waitingForACaller())) {
            continue;
        }

        // `new XJob(` in a Feature or Operation, or `XJob::class` in a container call.
        if (preg_match("/(new {$job}\\(|{$job}::class)/", appSource()) !== 1) {
            $unused[] = $job;
        }
    }

    expect($unused)->toBe([], 'Jobs nothing runs: '.implode(', ', $unused));
});

/**
 * The allow-list has to shrink, and it can only shrink if something checks.
 *
 * A permanent exceptions list stops being read. This one is checked from both ends: a Job that has
 * since been deleted must come off it, and — more importantly — a Job that has since GAINED a caller
 * must come off it too, or the guard silently stops covering a unit that is now live.
 */
it('keeps the waiting list honest', function (): void {
    $gone = [];
    $live = [];

    foreach (waitingForACaller() as $job => $step) {
        if (! in_array($job, classNames('app/Domains', 'Job'), true)) {
            $gone[] = $job;

            continue;
        }

        if (preg_match("/(new {$job}\\(|{$job}::class)/", appSource()) === 1) {
            $live[] = "{$job} ({$step})";
        }
    }

    expect($gone)->toBe([], 'Listed but deleted — remove from the list: '.implode(', ', $gone));
    expect($live)->toBe([], 'Now has a caller — remove from the list: '.implode(', ', $live));
});

/**
 * A controller in this architecture is a routing detail: one `serve()` and nothing else. Logic that
 * creeps in here is logic outside a Feature, where no test looks for it.
 */
it('keeps controllers to a single serve call each', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app/Services'))->path('Controllers')->name('*.php') as $file) {
        $source = $file->getContents();
        $path = str_replace(base_path().'/', '', (string) $file->getRealPath());

        preg_match_all('/public function [a-zA-Z]+\s*\([^)]*\)[^{]*\{(.*?)\n    \}/s', $source, $bodies);

        foreach ($bodies[1] as $body) {
            /*
             * Collapsed to ONE line before counting, because a `serve()` call with several named
             * arguments is formatted across four or five of them. The first version counted lines
             * and flagged the roll-call controller for being wrapped by Pint — a guard that fails on
             * formatting is a guard people switch off.
             */
            $collapsed = trim(preg_replace('/\s+/', ' ', $body) ?? '');

            $isSingleServe = preg_match('/^return \$this->serve\(.*\);$/', $collapsed) === 1;

            if (! $isSingleServe) {
                $offenders[] = $path;
            }
        }
    }

    expect(array_unique($offenders))->toBe(
        [],
        'Controllers doing more than serving: '.implode(', ', array_unique($offenders)),
    );
});
