<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * The PHP language rules the `php` skill states and Pint cannot enforce — ported from aegis's `PhpStyleArchTest`
 * (its rules 31–35) so the two projects fail on the same things.
 *
 * One difference: aegis skips `App\Providers` for strict types. Nothing here earns that — a provider is code like
 * any other, and `E2EServiceProvider` is exactly where a silent int/string coercion would be hardest to see.
 *
 * Every rule reads comment-stripped source over every file under `app/` except colocated tests. Gaps are keyed by
 * path relative to `app/` rather than by class, because a service's route file (`app/Services/X/routes/web.php`)
 * is under `app/` too and declares no class.
 */

/** A call to one of the named global functions — `\\`-prefixed too — but not a method or a namespaced namesake. */
function callsGlobalFunction(string $code, string ...$functions): bool
{
    return preg_match('/(?<![\w:>$\\\\])\\\\?('.implode('|', $functions).')\s*\(/', $code) === 1;
}

/**
 * @param  callable(string): bool  $violates  given the comment-stripped source
 * @return list<string>
 */
function phpStyleOffenders(callable $violates): array
{
    $offenders = [];

    foreach (phpFiles() as $file) {
        if ($violates(codeOf($file))) {
            $offenders[] = relativePath($file);
        }
    }

    return $offenders;
}

it('finds the files it is supposed to be guarding', function (): void {
    expect(count(phpFiles()))->toBeGreaterThan(100);
});

it('declares strict types in every file', function (): void {
    expectOnlyKnownGaps(
        phpStyleOffenders(fn (string $code): bool => preg_match('/^<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $code) !== 1),
        KnownGaps::PHP_NO_STRICT_TYPES,
        'Start the file with declare(strict_types=1)',
    );
});

it('uses match, never switch', function (): void {
    expectOnlyKnownGaps(
        phpStyleOffenders(fn (string $code): bool => preg_match('/\bswitch\s*\(/', $code) === 1),
        KnownGaps::PHP_SWITCH,
        'Use match() instead of switch',
    );
});

it('uses Str:: rather than str_contains, str_starts_with and str_ends_with', function (): void {
    // One way to ask the question, so a reader never wonders whether the two differ (they do not — Str:: also
    // takes a list of needles, which is why it is the one kept).
    expectOnlyKnownGaps(
        phpStyleOffenders(fn (string $code): bool => callsGlobalFunction($code, 'str_contains', 'str_starts_with', 'str_ends_with')),
        KnownGaps::PHP_NATIVE_STR,
        'Use Str::contains / Str::startsWith / Str::endsWith',
    );
});

it('uses Collections rather than array_find, array_any and array_all', function (): void {
    expectOnlyKnownGaps(
        phpStyleOffenders(fn (string $code): bool => callsGlobalFunction($code, 'array_find', 'array_find_key', 'array_any', 'array_all')),
        KnownGaps::PHP_ARRAY_84,
        'Use collect()->first() / ->contains() / ->every()',
    );
});

it('gives every class constant a visibility', function (): void {
    expectOnlyKnownGaps(
        phpStyleOffenders(fn (string $code): bool => preg_match('/^\s*(final\s+)?const\s+/m', $code) === 1),
        KnownGaps::PHP_CONST_VISIBILITY,
        'Write public/protected/private const',
    );
});

it('sees a global function however it is written', function (string $code, bool $calls): void {
    // The guard on the two rules above: `\\str_contains(` slipped through the first version.
    expect(callsGlobalFunction($code, 'str_contains', 'array_find'))->toBe($calls);
})->with([
    'plain' => ['str_contains($a, $b)', true],
    'fully qualified' => ['\\str_contains($a, $b)', true],
    'after a bang' => ['if (!array_find($a, $f))', true],
    'a method' => ['$s->str_contains($a)', false],
    'a static' => ['Str::str_contains($a)', false],
    'a namespaced namesake' => ['Foo\\str_contains($a)', false],
    'a longer name' => ['my_str_contains($a)', false],
]);
