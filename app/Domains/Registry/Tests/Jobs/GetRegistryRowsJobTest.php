<?php

declare(strict_types=1);

use App\Domains\Registry\DataObjects\RegistryRowData;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;

/*
 * The sheet has one row per CHILD with the parent repeated (plan §7). A synthetic CSV with the real column
 * headers — never real families.
 */

/** @return list<RegistryRowData> */
function sampleRegistryRows(): array
{
    return (new GetRegistryRowsJob(path: base_path('tests/Fixtures/registry-sample.csv')))->handle();
}

it('reads and normalises every row', function (): void {
    $rows = sampleRegistryRows();
    $alexis = $rows[0];

    expect($rows)->toHaveCount(6)
        ->and($alexis->child_first_name)->toBe('Alexis')
        ->and($alexis->nickname)->toBe('Amara')
        ->and($alexis->gender)->toBe(GenderEnum::Girl)
        ->and($alexis->date_of_birth)->toBe('2019-05-10')
        ->and($alexis->email)->toBe('janice@example.com')
        ->and($alexis->phone_key)->toBe('544897346')
        ->and($alexis->allergies)->toBe('Peanuts')
        ->and($alexis->status)->toBe(ChildStatusEnum::Active);
});

it('gives every child a stable key that does not depend on row order', function (): void {
    $rows = sampleRegistryRows();
    $again = sampleRegistryRows();

    expect($rows[0]->source_key)->toBe($again[0]->source_key)
        ->and($rows[0]->source_key)->not->toBe($rows[1]->source_key);
});

it('maps the status column', function (): void {
    $rows = sampleRegistryRows();

    expect($rows[2]->is_duplicate)->toBeTrue()
        ->and($rows[3]->status)->toBe(ChildStatusEnum::Graduated)
        ->and($rows[4]->status)->toBe(ChildStatusEnum::Inactive);
});

// Found in review B2.
it('keeps a short or malformed record as a row instead of dropping it silently', function (): void {
    $path = sys_get_temp_dir().'/registry-short-'.uniqid().'.csv';
    $lines = file(base_path('tests/Fixtures/registry-sample.csv')) ?: [];
    file_put_contents($path, $lines[0]."Lonely,Row,Only three columns\n");

    $rows = (new GetRegistryRowsJob(path: $path))->handle();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->child_first_name)->toBe('Lonely')
        ->and($rows[0]->email)->toBe('');
});
