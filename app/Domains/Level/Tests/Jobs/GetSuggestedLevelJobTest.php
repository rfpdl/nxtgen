<?php

declare(strict_types=1);

use App\Domains\Level\Jobs\GetSuggestedLevelJob;

use function Tests\Support\church;

/*
 * The Level a child's age points to — a SUGGESTION only (GLOSSARY "Level"): age in completed years on the church's
 * Moving-Up reference date, against the church-wide chart in config/nxtgen.php (CCF's age-level chart).
 */

it('suggests the Level for the child\'s age on the reference date', function (string $dateOfBirth, ?int $level): void {
    $church = church(['moving_up_reference_date' => '2027-02-01']);

    expect((new GetSuggestedLevelJob(church: $church, dateOfBirth: $dateOfBirth))->handle())->toBe($level);
})->with([
    'too young (3)' => ['2023-06-01', null],
    'age 4 → Level 1' => ['2022-06-01', 1],
    'age 5, a day short of 6 → Level 1' => ['2021-02-02', 1],
    'age 6 on the day → Level 2' => ['2021-02-01', 2],
    'age 7 → Level 2' => ['2019-06-01', 2],
    'age 9 → Level 3' => ['2017-03-14', 3],
    'age 10 → Level 4' => ['2016-06-01', 4],
    'age 12 → Level 4' => ['2014-06-01', 4],
    'too old (13)' => ['2013-06-01', null],
]);

it('measures age on today when the church has no reference date', function (): void {
    $church = church(['moving_up_reference_date' => null]);

    expect((new GetSuggestedLevelJob(church: $church, dateOfBirth: now()->subYears(6)->toDateString()))->handle())->toBe(2);
});

it('follows the chart in config, not a list in code', function (): void {
    config(['nxtgen.levels' => [1 => ['min_age' => 3, 'max_age' => 12, 'by_gender' => false]]]);
    $church = church(['moving_up_reference_date' => '2027-02-01']);

    expect((new GetSuggestedLevelJob(church: $church, dateOfBirth: '2023-06-01'))->handle())->toBe(1);
});
