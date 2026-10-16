<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Every Job and every Enum HAS a test — ported from aegis's `TestCoverageArchTest`.
 *
 * Aegis found the gap the hard way: three enums and a rewritten Job shipped untested with the gate green, because
 * the location guard only sees tests in the wrong place and the enum guard only sees an enum's shape. A unit with
 * no test at all was invisible to both.
 *
 * Attributed by FILE, at the path the spec names (batch 08): a Job's test is
 * `app/Domains/<Domain>/Tests/Jobs/<Job>Test.php`, an enum's is `tests/Unit/Enums/<Enum>Test.php`. A bundled file
 * (batch 08 split `MagicLinkJobsTest`, which exercised three Jobs) leaves every Job in it unattributable, which is
 * what this enforces against — aegis matches a basename anywhere; here the path is fixed because
 * `DomainTestLocationTest` already says where a domain test lives.
 *
 * Feature tests are not counted per Feature here: each Feature's #[UserStory] criteria are, by StoryTokenTest.
 */
function jobTestPath(string $jobFile): string
{
    return (string) preg_replace('#/Jobs/(\w+)\.php$#', '/Tests/Jobs/$1Test.php', $jobFile);
}

it('finds the units it is supposed to be guarding', function (): void {
    expect(count(jobFiles()))->toBeGreaterThan(10);
    expect(count(enumFiles()))->toBeGreaterThan(10);
});

it('has a test for every Job, beside it in Tests/Jobs', function (): void {
    $untested = array_values(array_map(
        fn (string $f): string => basename($f, '.php'),
        array_filter(jobFiles(), fn (string $f): bool => ! is_file(jobTestPath($f))),
    ));

    expectOnlyKnownGaps($untested, KnownGaps::JOBS_WITHOUT_TESTS, 'Write app/Domains/<Domain>/Tests/Jobs/<Job>Test.php');
});

it('has a test for every enum, in tests/Unit/Enums', function (): void {
    $untested = array_values(array_map(
        fn (string $f): string => basename($f, '.php'),
        array_filter(enumFiles(), fn (string $f): bool => ! is_file(base_path('tests/Unit/Enums/'.basename($f, '.php').'Test.php'))),
    ));

    expectOnlyKnownGaps($untested, KnownGaps::ENUMS_WITHOUT_TESTS, 'Write tests/Unit/Enums/<Enum>Test.php');
});

it('finds a Job test where it says it looks', function (): void {
    // The guard on the guard: if jobTestPath() drifted from where tests really are, every Job would read as
    // untested and the known-gaps list would quietly absorb all of them.
    expect(jobTestPath(base_path('app/Domains/Family/Jobs/CreateChildPhotoJob.php')))->toBeFile();
    expect(relativePath(jobTestPath(base_path('app/Domains/Family/Jobs/CreateChildPhotoJob.php'))))
        ->toBe('Domains/Family/Tests/Jobs/CreateChildPhotoJobTest.php');
});
