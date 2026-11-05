<?php

declare(strict_types=1);

use Tests\Support\TestMap;

/*
 * The test map (`make test-map`, TODO #10): the access matrix as a page, each story with the tests that prove it. Its
 * data comes from the lists the tests themselves use (AccessMatrix, StoryClaims), so it shows what the suite proves.
 */

it('shows each staff route with its rule, who gets in, and each story\'s tests', function (): void {
    $map = TestMap::export(base_path());
    $route = collect($map['routes'])->firstWhere('name', 'church.moving-up.store') ?? throw new LogicException('No Moving-Up route on the map.');

    expect($route['method'])->toBe('POST')
        ->and($route['uri'])->toBe('/{church}/moving-up')
        ->and($route['feature'])->toBe('MoveChildrenUpFeature')
        ->and($route['rule'])->toContain('church admin only')
        ->and($route['cells'])->toMatchArray(['church admin' => 'ok', 'satellite admin' => '404', 'anonymous' => 'login']);

    $happy = collect($route['stories'])->firstWhere('kind', 'happy') ?? throw new LogicException('No happy path.');
    expect($happy['token'])->toBe('story#156')
        ->and(collect($happy['tests'])->pluck('file')->all())->toContain(
            'tests/Feature/Services/Church/ChurchMovingUpTest.php',
            'tests/e2e/staff/moving-up.spec.ts',
        );
});

it('lists every staff and parent route, and every other Feature once', function (): void {
    $map = TestMap::export(base_path());
    $features = collect($map['features'])->pluck('feature');

    expect(count($map['routes']))->toBeGreaterThan(40)
        ->and($features)->toContain('RequestMagicLinkFeature', 'SweepChildPhotosFeature')
        ->and($features)->not->toContain('MoveChildrenUpFeature')
        ->and($features->duplicates()->all())->toBe([]);
});

it('has a test for every story, as StoryTokenTest requires', function (): void {
    expect(TestMap::export(base_path())['untested'])->toBe([]);
});

it('carries each test\'s file, title and kind — what a Run hands to Pest or Playwright', function (): void {
    $tests = collect(TestMap::export(base_path())['routes'])
        ->flatMap(fn (array $r): array => $r['stories'])
        ->flatMap(fn (array $s): array => $s['tests']);

    expect($tests->pluck('kind')->unique()->sort()->values()->all())->toBe(['e2e', 'php'])
        ->and($tests->every(fn (array $t): bool => is_file(base_path($t['file'])) && $t['title'] !== ''))->toBeTrue();
});
