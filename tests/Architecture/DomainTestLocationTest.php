<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Symfony\Component\Finder\Finder;

/**
 * A domain's tests live inside the domain, mirroring what they test — ported from aegis's
 * `DomainTestLocationArchTest`.
 *
 * `app/Domains/<Domain>/Tests/<Sub>/<Unit>Test.php` tests `app/Domains/<Domain>/<Sub>/<Unit>.php`: a Job's test in
 * `Tests/Jobs`, a DataObject's in `Tests/DataObjects`. phpunit.xml's Domain suite runs whatever is there, so a
 * stray file still runs — this keeps the LOCATION honest, so `TestCoverageTest` can find a unit's test by path.
 *
 * Aegis guards only the stray directories (`tests/Unit/Domains`, `tests/Feature/Jobs`); the mirroring rule is
 * added here because nxtgen had the drift it prevents — a bundled `MagicLinkJobsTest`, split in batch 08.
 */

/** @return list<string> relative to `app/` */
function domainTestFiles(): array
{
    $files = [];

    foreach (Finder::create()->files()->in(base_path('app/Domains'))->path('#^[^/]+/Tests/#')->name('*Test.php') as $file) {
        $files[] = 'Domains/'.$file->getRelativePathname();
    }

    sort($files);

    return $files;
}

it('finds the domain tests it is supposed to be guarding', function (): void {
    expect(count(domainTestFiles()))->toBeGreaterThan(3);
});

it('keeps domain tests out of tests/Unit/Domains, tests/Feature/Domains and tests/Feature/Jobs', function (): void {
    $stray = [];

    foreach (['tests/Unit/Domains', 'tests/Feature/Domains', 'tests/Feature/Jobs', 'tests/Unit/Jobs'] as $dir) {
        if (is_dir(base_path($dir))) {
            foreach (Finder::create()->files()->in(base_path($dir))->name('*Test.php') as $file) {
                $stray[] = $dir.'/'.$file->getRelativePathname();
            }
        }
    }

    expect($stray)->toBe([], "These belong in app/Domains/<Domain>/Tests/<same sub-path>:\n  ".implode("\n  ", $stray));
});

it('names each domain test for the unit it sits beside', function (): void {
    $unmirrored = array_values(array_filter(
        domainTestFiles(),
        fn (string $test): bool => ! is_file(base_path('app/'.preg_replace('#/Tests/(.*)Test\.php$#', '/$1.php', $test))),
    ));

    expectOnlyKnownGaps($unmirrored, KnownGaps::DOMAIN_TEST_NOT_MIRRORED, 'Name the test <Unit>Test.php under Tests/<the unit\'s folder>');
});
