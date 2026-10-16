<?php

declare(strict_types=1);

namespace Tests\Architecture;

use ReflectionClass;
use Symfony\Component\Finder\Finder;

use function expect;

/*
 * Shared helpers for the guards ported from aegis (`core/tests/Architecture/Helpers.php`) in refactor batch 03.
 *
 * NAMESPACED on purpose. The older guards in this directory declare their helpers as global functions
 * (`featureFiles()`, `jobFiles()`, …) and Pest loads every test file into one process, so a second global
 * `jobFiles()` would be a fatal redeclaration. Importing these with `use function Tests\Architecture\…`
 * also says where a helper came from. Required once from tests/Pest.php, like tests/Support.
 */

function appPath(string $path = ''): string
{
    return dirname(__DIR__, 2).'/app'.($path === '' ? '' : '/'.$path);
}

/**
 * Every PHP file under `app/{$directory}`, absolute and sorted — colocated tests (`app/Domains/X/Tests`) excluded,
 * because a test is not production code and every rule here is about production code.
 *
 * @return list<string>
 */
function phpFiles(string $directory = '', string $name = '*.php'): array
{
    if (! is_dir(appPath($directory))) {
        return [];
    }

    $files = [];

    foreach (Finder::create()->files()->in(appPath($directory))->exclude('Tests')->name($name) as $file) {
        $files[] = (string) $file->getRealPath();
    }

    sort($files);

    return $files;
}

/** `app/Domains/Family/Jobs/X.php` → `Domains/Family/Jobs/X.php` — the form the path-keyed gap lists use. */
function relativePath(string $file): string
{
    return substr($file, strlen(appPath()) + 1);
}

/** PSR-4: `app/Domains/Family/Jobs/X.php` → `App\Domains\Family\Jobs\X`. */
function pathToFqcn(string $file): string
{
    return 'App\\'.str_replace(['/', '.php'], ['\\', ''], relativePath($file));
}

/** @return ReflectionClass<object>|null the class (or enum, or interface) the file declares, if it autoloads */
function reflectFromPath(string $file): ?ReflectionClass
{
    $fqcn = pathToFqcn($file);

    if (! class_exists($fqcn) && ! interface_exists($fqcn) && ! enum_exists($fqcn)) {
        return null;
    }

    return new ReflectionClass($fqcn);
}

/**
 * The files whose class does not load from the name its path gives it — a typo'd namespace, a renamed class.
 * Every reflection-based guard SKIPS such a file (there is nothing to reflect), so this is what stops a skip from
 * being silent: `BaseClassTest` asserts it is empty for every layer the guards read.
 *
 * @param  list<string>  $files
 * @return list<string> paths relative to `app/`
 */
function unloadable(array $files): array
{
    return array_values(array_map(relativePath(...), array_filter($files, fn (string $f): bool => reflectFromPath($f) === null)));
}

/**
 * @param  list<string>  $files
 * @return list<ReflectionClass<object>> the loadable ones (see `unloadable()` for the rest)
 */
function classesIn(array $files): array
{
    return array_values(array_filter(array_map(reflectFromPath(...), $files), fn (?ReflectionClass $r): bool => $r !== null));
}

/**
 * The source with comments removed, by the TOKENISER — a docblock in this codebase often quotes the very query
 * it replaced, and a guard that reads prose fails on explanations of why there is no violation.
 */
function stripComments(string $source): string
{
    $result = '';

    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                $result .= $token[1];
            }

            continue;
        }

        $result .= $token;
    }

    return $result;
}

function codeOf(string $file): string
{
    return stripComments((string) file_get_contents($file));
}

/** @return list<string> */
function jobFiles(): array
{
    return array_values(array_filter(phpFiles('Domains', '*Job.php'), fn (string $f): bool => str_contains($f, '/Jobs/')));
}

/** @return list<string> */
function operationFiles(): array
{
    return phpFiles('Operations', '*Operation.php');
}

/** @return list<string> every Feature, the abstract `Base…Feature`s included */
function featureFiles(): array
{
    return phpFiles('Services', '*Feature.php');
}

/**
 * The app-wide middleware and each service's own (`app/Services/X/Http/Middleware`).
 *
 * @return list<string>
 */
function middlewareFiles(): array
{
    return [
        ...phpFiles('Http/Middleware'),
        ...array_values(array_filter(phpFiles('Services'), fn (string $f): bool => str_contains($f, '/Http/Middleware/'))),
    ];
}

/**
 * Response DTOs: `app/Domains/X/DataObjects/*Data.php`.
 *
 * @return list<string>
 */
function dataObjectFiles(): array
{
    return array_values(array_filter(phpFiles('Domains', '*Data.php'), fn (string $f): bool => str_contains($f, '/DataObjects/')));
}

/**
 * Inputs: a service's own (`app/Services/X/Data`) and the ones several services share (`app/Foundation/Inputs`).
 *
 * @return list<string>
 */
function inputFiles(): array
{
    return [
        ...array_values(array_filter(phpFiles('Services', '*Input.php'), fn (string $f): bool => str_contains($f, '/Data/'))),
        ...phpFiles('Foundation/Inputs', '*Input.php'),
    ];
}

/** @return list<string> the enums — not `Enums/Concerns`, which holds the interfaces and trait they share */
function enumFiles(): array
{
    return array_values(array_filter(phpFiles('Enums'), fn (string $f): bool => ! str_contains($f, '/Enums/Concerns/')));
}

/** @return list<string> the model basenames, e.g. `Family` — the classes a static query can start from */
function modelNames(): array
{
    return array_map(fn (string $f): string => basename($f, '.php'), phpFiles('Data/Models'));
}

/**
 * Static calls on a model that are NOT queries: a model's own event hooks, factories (tests), and
 * `DuplicateCandidate::familyOf()`, which only picks a relation off a record already loaded. Everything else
 * called statically on a model — `query()`, `where()`, `with()`, `find()`, `all()`, a scope, `Note::system()` —
 * reaches the database.
 *
 * @return list<string>
 */
function nonQueryModelStatics(): array
{
    return ['factory', 'familyOf', 'observe', 'booted', 'creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted'];
}

/**
 * THE way into a model's rows, shared by every guard that refuses or inspects one (Feature, Operation, Middleware,
 * Tenancy) — aegis learned the hard way that copies drift: three copies, and a `whereIn` passed in an Operation.
 *
 * An allow-list of the statics that are NOT queries, rather than a list of the ones that are: a list of query
 * starters is never complete (`with`, `latest`, `orderBy`, any scope…), and the gap is always the method nobody
 * thought of. Anchored to the MODEL names, so `Carbon::create()` or `Storage::delete()` are not queries, and the
 * name may be fully or partly qualified (`\App\Data\Models\Family::query()`, `Models\Family::where()`).
 * Group 1 is the model's short name, group 2 the method.
 *
 * @param  list<string>|null  $models  short names; every model when null
 */
function modelEntryPattern(?array $models = null): string
{
    $names = implode('|', $models ?? modelNames());
    $allowed = implode('|', nonQueryModelStatics());

    return '/(?<![\w\\\\$>:])(?:\\\\?(?:\w+\\\\)*)?\b('.$names.')::(?!(?:'.$allowed.')\b)(\w+)\s*\(/';
}

/** Writes on an already-loaded model — invisible to the static pattern, same rule. */
function eloquentInstanceWritePattern(): string
{
    $methods = [
        'save', 'saveQuietly', 'update', 'updateQuietly', 'delete', 'forceDelete', 'restore',
        'increment', 'decrement', 'forceFill', 'push',
    ];

    return '/\$\w+(->\w+)*->('.implode('|', $methods).')\s*\(/';
}

/**
 * A Job run by hand — `->handle()` called on it outside a Lucid unit's `run()`, which is what types the call
 * (stubs/Lucid.stub) and makes it a unit like any other. Three shapes:
 *
 * - `(new XJob(…))->handle()` — balanced parentheses, so named arguments that call functions are one match;
 * - `app(XJob::class, […])->handle()`, `resolve(…)`, `->make(…)`;
 * - `$job = new XJob(…)` (or `= app(XJob::class)`), then `$job->handle()` anywhere later in the file.
 *
 * Still blind to a Job handed in from elsewhere (a parameter, a property) and then handled — nothing in this
 * codebase does that, and a guard that tried would need to follow types.
 */
function runsJobByHand(string $code): bool
{
    $job = '\\\\?[\w\\\\]*Job';

    if (preg_match('/\(\s*new\s+'.$job.'\s*(\((?:[^()]++|(?1))*\))\s*\)\s*->handle\s*\(/', $code) === 1) {
        return true;
    }

    if (preg_match('/(?:\b(?:app|resolve)|->make(?:With)?)\s*\(\s*'.$job.'::class\b[^;]*?\)\s*->handle\s*\(/', $code) === 1) {
        return true;
    }

    preg_match_all('/\$(\w+)\s*=\s*(?:new\s+'.$job.'\b|(?:app|resolve)\s*\(\s*'.$job.'::class)/', $code, $vars);

    foreach (array_unique($vars[1]) as $var) {
        if (preg_match('/\$'.$var.'->handle\s*\(/', $code) === 1) {
            return true;
        }
    }

    return false;
}

/**
 * @param  list<string>  $files
 * @return list<string> FQCNs of the files that run a Job by hand
 */
function fqcnsRunningJobsByHand(array $files): array
{
    return array_values(array_map(pathToFqcn(...), array_filter($files, fn (string $f): bool => runsJobByHand(codeOf($f)))));
}

/**
 * The files whose comment-stripped source matches the regex, as FQCNs.
 *
 * @param  list<string>  $files
 * @return list<string>
 */
function fqcnsMatching(array $files, string $regex): array
{
    return array_values(array_map(
        pathToFqcn(...),
        array_filter($files, fn (string $f): bool => preg_match($regex, codeOf($f)) === 1),
    ));
}

/**
 * Lines that are code: not blank and not a comment. Measured by the
 * tokeniser, the same as aegis's `MeasureClassLengthsJob` — docblocks here carry the "why", and a budget that
 * charged for them would punish exactly the documentation habit the project wants.
 */
function codeLineCount(string $file): int
{
    $lines = explode("\n", stripComments((string) file_get_contents($file)));

    return count(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
}

/**
 * The ratchet every new guard uses: what violates today must be exactly what the list says.
 *
 * - Something violating that is NOT listed fails: the rule is new debt being added.
 * - Something listed that NO LONGER violates fails too: the debt was paid, so the entry must go, or the list
 *   becomes a lie and quietly exempts the next regression in that file. This is aegis's "only shrink" check and
 *   the same idea as ReachabilityTest's honesty test.
 *
 * @param  list<string>  $found
 * @param  list<string>  $known
 */
function expectOnlyKnownGaps(array $found, array $known, string $rule): void
{
    $new = array_values(array_diff($found, $known));
    $paid = array_values(array_diff($known, $found));

    expect($new)->toBe([], "{$rule}:\n  ".implode("\n  ", $new));
    expect($paid)->toBe(
        [],
        "Listed in KnownGaps but no longer a violation of \"{$rule}\" — delete the entry so the list only shrinks:\n  "
        .implode("\n  ", $paid),
    );
}
