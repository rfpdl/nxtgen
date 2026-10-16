<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

use function Tests\Architecture\pathToFqcn;
use function Tests\Architecture\stripComments;

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
 * The classes under `app/{$directory}` whose name ends in the suffix, as FULLY QUALIFIED names.
 *
 * **FQCNs, not basenames (TODO #19).** This used to collect `getFilenameWithoutExtension()` and look for
 * `AddChildFeature::class` anywhere in `app/` — and there are TWO `AddChildFeature`s (Family and Org), two
 * `RegisterFamilyFeature`s, two `ShowFamilyFeature`s and two `ShowRegisterFeature`s. With basenames, serving one
 * of a pair made both look served, so a Feature whose route had been deleted would have passed.
 *
 * @return list<string>
 */
function classNames(string $directory, string $suffix): array
{
    if (! is_dir(base_path($directory))) {
        return [];
    }

    $names = [];

    foreach (Finder::create()->files()->in(base_path($directory))->exclude('Tests')->name("*{$suffix}.php") as $file) {
        $names[] = pathToFqcn((string) $file->getRealPath());
    }

    sort($names);

    return array_values(array_unique($names));
}

/**
 * Every class referenced as `new X(` or `X::class` anywhere in `app/`, resolved to its FQCN through the file's
 * namespace and `use` imports — the same resolution PHP does, so two classes that share a short name are told
 * apart. A file's references to ITSELF are not counted: a class cannot make itself reachable.
 *
 * `exclude('Tests')`, and a colocated test is what found the need for it: `phpunit.xml` runs the domain tests from
 * `app/Domains/X/Tests`, and without this a Job's own test made the Job look like it had a production caller.
 * A test is not a caller. It proves the unit works; it does not prove anybody can get to it.
 *
 * @return array<string, true>
 */
function referencedClasses(): array
{
    /** @var array<string, true>|null $referenced */
    static $referenced = null;

    if ($referenced !== null) {
        return $referenced;
    }

    $referenced = [];

    foreach (Finder::create()->files()->in(base_path('app'))->exclude('Tests')->name('*.php') as $file) {
        foreach (referencesIn(stripComments($file->getContents()), pathToFqcn((string) $file->getRealPath())) as $fqcn) {
            $referenced[$fqcn] = true;
        }
    }

    return $referenced;
}

/**
 * The FQCNs one file's code references by `new X(` or `X::class`, other than itself.
 *
 * @return list<string>
 */
function referencesIn(string $code, string $self): array
{
    $namespace = preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $code, $ns) === 1 ? $ns[1] : '';

    $imports = [];
    preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m', $code, $uses, PREG_SET_ORDER);
    foreach ($uses as $use) {
        $alias = ($use[2] ?? '') !== '' ? $use[2] : substr((string) strrchr('\\'.$use[1], '\\'), 1);
        $imports[$alias] = $use[1];
    }

    $found = [];
    preg_match_all('/new\s+(\\\\?[A-Za-z_][\w\\\\]*)\s*\(|(\\\\?[A-Za-z_][\w\\\\]*)::class\b/', $code, $refs, PREG_SET_ORDER);
    foreach ($refs as $ref) {
        $name = ($ref[1] ?? '') !== '' ? $ref[1] : ($ref[2] ?? '');
        $first = explode('\\', ltrim($name, '\\'))[0];

        $fqcn = match (true) {
            str_starts_with($name, '\\') => ltrim($name, '\\'),
            isset($imports[$first]) => $imports[$first].substr($name, strlen($first)),
            default => ltrim($namespace.'\\'.$name, '\\'),
        };

        if ($fqcn !== $self) {
            $found[] = $fqcn;
        }
    }

    return array_values(array_unique($found));
}

function isReferenced(string $fqcn): bool
{
    return isset(referencedClasses()[$fqcn]);
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
 * Jobs allowed to exist before their caller does, keyed by FQCN. Empty. Add a Job here only with the screen
 * that will call it; the list must shrink, never rot.
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
        if (str_starts_with(class_basename($feature), 'Base')) {
            continue;
        }

        if (! isReferenced($feature)) {
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
        if (str_starts_with(class_basename($job), 'Base') || array_key_exists($job, waitingForACaller())) {
            continue;
        }

        // `new XJob(` in a Feature or Operation, or `XJob::class` in a container call.
        if (! isReferenced($job)) {
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

        if (isReferenced($job)) {
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
it('tells two classes with the same short name apart', function (): void {
    // The guard on the TODO #19 fix, on a real pair: Org and Family both have an AddChildFeature. A controller
    // that imports ONE of them references that one only — with basenames, it made both look served.
    $controller = <<<'PHP'
        <?php
        namespace App\Services\Org\Http\Controllers\Family;

        use App\Services\Org\Features\Family\AddChildFeature;
        use App\Services\Org\Features\Family\ShowFamilyFeature as Show;

        final class Controller
        {
            public function a(): mixed { return $this->serve(AddChildFeature::class); }
            public function b(): mixed { return $this->serve(Show::class); }
            public function c(): mixed { return new Helper(); }
        }
        PHP;

    expect(referencesIn($controller, 'App\Services\Org\Http\Controllers\Family\Controller'))->toBe([
        'App\Services\Org\Features\Family\AddChildFeature',
        'App\Services\Org\Features\Family\ShowFamilyFeature',
        'App\Services\Org\Http\Controllers\Family\Helper',
    ]);
});

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
