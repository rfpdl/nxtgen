<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Symfony\Component\Finder\Finder;

/**
 * Migration rules, ported from aegis's `MigrationArchTest` and cut down to the ones that mean something on MySQL.
 *
 * **On MySQL `timestampTz()` and `timestamp()` make the same column** — MySQL has no zoned type, and Laravel's
 * grammar maps both to `timestamp`. The rule is kept anyway, for two reasons: the codebase already says
 * `timestampsTz()` everywhere, so one spelling is the honest description of what a column holds (a UTC instant,
 * never a wall-clock time); and the day this schema meets Postgres, the plain spelling silently changes meaning.
 * The UTC rule below is what makes the instant true on MySQL.
 *
 * Kept as-is: the `->comment()` ORDER rule. Laravel's `constrained()` and `*OnDelete()` return a
 * ForeignKeyDefinition on every driver, so a comment chained after them is dropped on MySQL exactly as on Postgres.
 *
 * Not ported: aegis's `migrations-audit` / `migrations-logs` connections (nxtgen has one database).
 */

/** @return list<string> */
function migrationFiles(): array
{
    $files = [];

    foreach (Finder::create()->files()->in(database_path('migrations'))->name('*.php') as $file) {
        $files[] = (string) $file->getRealPath();
    }

    sort($files);

    return $files;
}

/**
 * Each migration's statements, comments stripped and folded onto one line, so a chain split across lines is one
 * unit (aegis's first version compared within a line and missed eleven split chains).
 *
 * @return list<array{file: string, statement: string}>
 */
function migrationStatements(): array
{
    $statements = [];

    foreach (migrationFiles() as $file) {
        $code = stripComments((string) file_get_contents($file));

        foreach (explode(';', $code) as $statement) {
            $statements[] = ['file' => basename($file), 'statement' => trim((string) preg_replace('/\s+/', ' ', $statement))];
        }
    }

    return $statements;
}

/** @return list<string> the migrations with a statement matching the regex */
function migrationsMatching(string $regex): array
{
    $files = [];

    foreach (migrationStatements() as ['file' => $file, 'statement' => $statement]) {
        if (preg_match($regex, $statement) === 1) {
            $files[] = $file;
        }
    }

    return array_values(array_unique($files));
}

it('finds the migrations it is supposed to be guarding', function (): void {
    expect(count(migrationFiles()))->toBeGreaterThan(5);
});

it('uses timestampTz, timestampsTz and softDeletesTz', function (): void {
    expectOnlyKnownGaps(
        migrationsMatching('/->(timestamp|timestamps|softDeletes|dateTime|nullableTimestamps)\s*\(/'),
        KnownGaps::MIGRATION_PLAIN_TIMESTAMP,
        'Use the Tz spelling — every stored time is a UTC instant',
    );
});

it('runs the application in UTC', function (): void {
    // The other half of the rule above. Eloquent writes a time as a zone-less string in PHP's default zone, which
    // Laravel sets from this; anything but UTC and the stored instant depends on which server wrote it.
    expect(config('app.timezone'))->toBe('UTC');
});

it('puts ->comment() before constrained() and *OnDelete(), where it is not silently dropped', function (): void {
    $chain = 'constrained|nullOnDelete|cascadeOnDelete|restrictOnDelete|noActionOnDelete|cascadeOnUpdate';
    $offenders = [];

    foreach (migrationStatements() as ['file' => $file, 'statement' => $statement]) {
        $chainAt = preg_match('/->('.$chain.')\s*\(/', $statement, $c, PREG_OFFSET_CAPTURE) === 1 ? $c[0][1] : null;
        $commentAt = preg_match('/->comment\s*\(/', $statement, $m, PREG_OFFSET_CAPTURE) === 1 ? $m[0][1] : null;

        if ($chainAt !== null && $commentAt !== null && $chainAt < $commentAt) {
            $offenders[] = "{$file}: {$statement}";
        }
    }

    expect($offenders)->toBe([], "Move ->comment() before the foreign-key chain:\n  ".implode("\n  ", $offenders));
});
