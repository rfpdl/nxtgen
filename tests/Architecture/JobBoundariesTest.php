<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * The two Lucid boundaries this codebase actually risks crossing.
 *
 * **The naming rules and the nesting rules are aegis's**, copied verbatim in intent so the two
 * projects fail on the same things. Roy's point when he read this code — *"you can see how palo and
 * aegis (mainly) built"* — was that the standard already exists and this project had adopted a
 * fraction of it.
 */
function jobFiles(): Finder
{
    return Finder::create()->files()->in(base_path('app/Domains'))->path('#^[^/]+/Jobs/#')->name('*.php');
}

/**
 * @return list<string>
 */
function jobNames(): array
{
    $names = [];

    foreach (jobFiles() as $file) {
        $names[] = $file->getBasename('.php');
    }

    return $names;
}

it('finds the Jobs it is supposed to be guarding', function (): void {
    expect(iterator_count(jobFiles()))->toBeGreaterThan(10);
});

/**
 * **A Job never opens a transaction.** The Feature or Operation does (plan §5.4).
 *
 * It matters most where two Jobs must commit together: `ReplaceLostCardFeature` marks a card lost and
 * mints its replacement, and the ORDER is load-bearing because the database allows only one live
 * permanent card per child. If either Job wrapped itself, the Feature's transaction would nest and
 * the two writes could no longer be undone as one.
 */
it('never opens a transaction inside a Job', function (): void {
    $offenders = [];

    foreach (jobFiles() as $file) {
        $source = $file->getContents();

        // `lockForUpdate` is fine and expected — a Job may take locks INSIDE the caller's
        // transaction. Beginning one is what is refused.
        if (preg_match('/DB::(transaction|beginTransaction)\s*\(/', $source) === 1) {
            $offenders[] = str_replace(base_path().'/', '', (string) $file->getRealPath());
        }
    }

    expect($offenders)->toBe([], 'Transactions belong to the Feature: '.implode(', ', $offenders));
});

/**
 * **A Domain never reaches into another Domain.** Crossing is what an Operation is for (plan §5.4).
 *
 * The whole value of the layer is that `Attendance` can be reasoned about without reading `IdCard`.
 * One import is all it takes for that to stop being true, and it is the kind of thing that looks
 * harmless in a diff.
 *
 * `App\Data\Models` is shared on purpose — models are the common vocabulary, not a domain.
 */
it('never lets one Domain import another', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app/Domains'))->name('*.php') as $file) {
        $path = str_replace(base_path().'/', '', (string) $file->getRealPath());
        $own = explode('/', $path)[2] ?? '';

        preg_match_all('/^use App\\\\Domains\\\\([A-Za-z]+)\\\\/m', $file->getContents(), $matches);

        foreach (array_unique($matches[1]) as $imported) {
            if ($imported !== $own) {
                $offenders[] = "{$path} imports {$imported}";
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
});

/**
 * Naming, copied from aegis's `JobArchTest` so the two projects fail on the same things.
 *
 * These are not style. A Job named for the STATE it produces (`UpdateCardsAsPrintedJob`) reads as a
 * one-way door and quietly resists the Job that has to undo it; a Job named for the update it
 * performs (`UpdateCardsAsPrintedJob`) does not. `Add`/`Store`/`Save` are the same problem from the
 * other side — three words for one thing, so a reader has to check which of them this codebase means.
 */
it('names Jobs for the update rather than the status', function (): void {
    $offenders = [];

    foreach (jobNames() as $job) {
        if (preg_match('/^(Mark|Complete)[A-Z]/', $job) === 1) {
            $offenders[] = $job;
        }
    }

    expect($offenders)->toBe(
        [],
        'Use Update{Entity}As{Status}Job: '.implode(', ', $offenders),
    );
});

it('uses Create rather than Add, Store or Save', function (): void {
    $offenders = [];

    foreach (jobNames() as $job) {
        if (preg_match('/^(Add|Store|Save)[A-Z]/', $job) === 1) {
            $offenders[] = $job;
        }
    }

    expect($offenders)->toBe([], 'Use Create{Entity}Job: '.implode(', ', $offenders));
});

it('does not put Record in a Job name', function (): void {
    // The entity name is enough. `RecordReleaseJob` says nothing `ReleaseJob` does not, and the extra
    // word invites a second Job called `SaveReleaseJob` that a reader has to distinguish from it.
    $offenders = [];

    foreach (jobNames() as $job) {
        if (preg_match('/Record(s)?Job$/', $job) === 1) {
            $offenders[] = $job;
        }
    }

    expect($offenders)->toBe([], 'Drop "Record": '.implode(', ', $offenders));
});

it('never runs a Job from inside a Job', function (): void {
    /*
     * A Lucid principle verbatim: "Jobs shall not call other jobs". Nesting turns the business logic
     * layer into a call graph nobody can hold in their head, and it is what Features are for.
     */
    $offenders = [];

    foreach (jobFiles() as $file) {
        $source = (string) preg_replace('#/\*.*?\*/#s', '', $file->getContents());

        if (preg_match('/\$this->run\(|new \w+Job\(/', $source) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe(
        [],
        'Jobs must not run other Jobs — that is the Feature\'s job: '.implode(', ', $offenders),
    );
});

it('never reaches for a Feature or an Operation from a Job', function (): void {
    // The dependency only points one way. A Job that knows about a Feature has inverted the
    // architecture, and the next caller of that Job inherits a whole screen.
    $offenders = [];

    foreach (jobFiles() as $file) {
        $source = (string) preg_replace('#/\*.*?\*/#s', '', $file->getContents());

        if (preg_match('/(\w+Feature|\w+Operation)(::class|\()/', $source) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([], 'Jobs must not know about Features or Operations: '.implode(', ', $offenders));
});
