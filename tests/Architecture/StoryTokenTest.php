<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Attributes\UserStory;
use Symfony\Component\Finder\Finder;

/**
 * Every Feature says what it is for, and every criterion it names is proved by a test — ported from aegis's
 * `StoryTokenArchTest`.
 *
 * A criterion is keyed by a story token (the word "story", a hash, a number) on the Feature's #[UserStory];
 * a test claims it by carrying the same token, at-prefixed, in its TITLE (a comment claims nothing). So a token must mean exactly ONE
 * criterion, and a token a test carries must exist. (Tokens are described in prose here, never written out:
 * this file is scanned with the other tests, and a literal would count as a claim.)
 *
 * Aegis only refuses duplicate tokens and unkeyed criteria; its catalog command reports coverage. There is no
 * catalog here, so this file is the whole gate: every Feature has a story, and every story has a test.
 */
const STORY_KEY = '/^story#(\d+)$/';

/** @return array<string, UserStory|null> concrete Feature FQCN => its story (null when it has none) */
function featureStories(): array
{
    $stories = [];

    foreach (classesIn(featureFiles()) as $class) {
        if ($class->isAbstract()) {
            continue;
        }

        $attributes = $class->getAttributes(UserStory::class);
        $stories[$class->getName()] = count($attributes) === 1 ? $attributes[0]->newInstance() : null;
    }

    return $stories;
}

/**
 * Story number => the Features that key it, read from the SOURCE as aegis does: a key repeated inside one
 * array literal is silently overwritten by PHP, so reflection would never see the duplicate.
 *
 * @return array<int, list<string>>
 */
function storyNumbers(): array
{
    $numbers = [];

    foreach (featureFiles() as $file) {
        preg_match_all("/'story#(\\d+)'\\s*=>/", codeOf($file), $m);

        foreach ($m[1] as $number) {
            $numbers[(int) $number][] = basename($file, '.php');
        }
    }

    ksort($numbers);

    return $numbers;
}

/** @return list<array{0: int|string, 1: string}> every criterion on the story, keyed or not */
function criteriaOf(?UserStory $story): array
{
    $criteria = [];

    foreach ([$story->happyPaths ?? [], $story->unhappyPaths ?? []] as $paths) {
        foreach ($paths as $key => $criterion) {
            $criteria[] = [$key, $criterion];
        }
    }

    return $criteria;
}

/**
 * The story numbers a test file claims: only tokens inside a test's TITLE — Pest's `it(`/`test(`/`describe(`,
 * Playwright's `test(` — with comments stripped first, so a note that mentions a story claims nothing.
 *
 * @return list<int>
 */
function storyClaimsIn(string $source, bool $php): array
{
    $code = $php
        ? stripComments($source)
        : (string) preg_replace(['#/\*.*?\*/#s', '#(?<![:\\\\])//[^\n]*#'], '', $source);

    preg_match_all('/\b(?:it|test|describe)(?:\.\w+)?\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1/s', $code, $titles);
    preg_match_all('/@story#(\d+)\b/', implode("\n", $titles[2]), $m);

    return array_values(array_unique(array_map(intval(...), $m[1])));
}

/** @return array<int, list<string>> story number => the test files that claim it */
function claimedStories(): array
{
    $finder = Finder::create()->files()->ignoreDotFiles(true)->exclude('node_modules')
        ->in([base_path('tests'), base_path('app/Domains')])
        ->name(['*Test.php', '*.spec.ts']);

    $claims = [];

    foreach ($finder as $file) {
        foreach (storyClaimsIn($file->getContents(), $file->getExtension() === 'php') as $number) {
            $claims[$number][] = $file->getRelativePathname();
        }
    }

    ksort($claims);

    return $claims;
}

it('counts a story only in a test title, never in a comment', function (): void {
    // The probe builds its tokens from parts, so this file does not claim them itself.
    $token = fn (int $n): string => '@'.'story#'.$n;
    $php = "<?php\n// it('noted only {$token(1)}', fn () => null);\n/* {$token(2)} */\nit('proves it {$token(3)}', fn () => null);\ndescribe(\"group {$token(4)}\", fn () => null);\n";
    $ts = "// test('noted only {$token(5)}')\ntest('a journey {$token(6)} {$token(7)}', async () => {})\n/* {$token(8)} */\ntest.describe(`journeys {$token(9)}`, () => {})\n";

    expect(storyClaimsIn($php, php: true))->toBe([3, 4])
        ->and(storyClaimsIn($ts, php: false))->toBe([6, 7, 9]);
});

it('finds the Features and the tests it is supposed to be guarding', function (): void {
    expect(count(featureStories()))->toBeGreaterThan(30)
        ->and(count(claimedStories()))->toBeGreaterThan(30);
});

it('gives every Feature exactly one #[UserStory], with at least one happy path', function (): void {
    $missing = array_keys(array_filter(featureStories(), fn (?UserStory $s): bool => $s === null || $s->happyPaths === []));

    expect($missing)->toBe([], "Add #[UserStory(title:, persona:, happyPaths: [token => criterion])] to:\n  ".implode("\n  ", $missing));
});

it('keys every criterion with a story token', function (): void {
    $unkeyed = [];

    foreach (featureStories() as $feature => $story) {
        foreach (criteriaOf($story) as [$key, $criterion]) {
            if (preg_match(STORY_KEY, (string) $key) !== 1) {
                $unkeyed[] = "{$feature}: {$criterion}";
            }
        }
    }

    expect($unkeyed)->toBe([], "Key each criterion with the next free story number:\n  ".implode("\n  ", $unkeyed));
});

it('never uses one story number for two criteria', function (): void {
    $shared = array_filter(storyNumbers(), fn (array $features): bool => count($features) > 1);

    expect($shared)->toBe([], 'Each story number keys exactly one criterion; give the newer one the next free number');
});

it('proves every story with at least one test that names it', function (): void {
    $unproved = array_values(array_diff(array_keys(storyNumbers()), array_keys(claimedStories())));

    expect($unproved)->toBe([], 'Write a test whose title carries the story token (at-prefixed) for story numbers: '.implode(', ', $unproved));
});

it('has no test claiming a story that does not exist', function (): void {
    $unknown = array_diff_key(claimedStories(), storyNumbers());

    expect($unknown)->toBe([], 'These tests name a story no Feature declares');
});
