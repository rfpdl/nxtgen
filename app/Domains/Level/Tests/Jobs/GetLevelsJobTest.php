<?php

declare(strict_types=1);

use App\Domains\Level\DataObjects\LevelOptionData;
use App\Domains\Level\Jobs\GetLevelsJob;

/* The church-wide Levels, in order, as a page offers them (a filter, the admin's correction). */

it('offers every Level of the chart, in order', function (): void {
    $levels = (new GetLevelsJob)->handle();

    expect(array_map(fn (LevelOptionData $l): array => [$l->level, $l->name], $levels))->toBe([
        [1, 'Level 1'], [2, 'Level 2'], [3, 'Level 3'], [4, 'Level 4'],
    ]);
});

it('follows the chart in config', function (): void {
    config(['nxtgen.levels' => [2 => ['min_age' => 6, 'max_age' => 7, 'by_gender' => true], 1 => ['min_age' => 4, 'max_age' => 5, 'by_gender' => false]]]);

    expect(array_map(fn (LevelOptionData $l): int => $l->level, (new GetLevelsJob)->handle()))->toBe([1, 2]);
});
