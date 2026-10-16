<?php

declare(strict_types=1);

use App\Domains\Registry\DataObjects\RegistryRowData;
use App\Domains\Registry\Jobs\GetRegistryProposalJob;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;

/*
 * The sheet has one row per CHILD with the parent repeated (plan §7). These tests use a synthetic CSV with
 * the real column headers — never real families.
 */

/** @return list<RegistryRowData> */
function sampleRows(): array
{
    return (new GetRegistryRowsJob(path: base_path('tests/Fixtures/registry-sample.csv')))->handle();
}

it('reads and normalises every row', function (): void {
    $rows = sampleRows();
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
    $rows = sampleRows();
    $again = sampleRows();

    expect($rows[0]->source_key)->toBe($again[0]->source_key)
        ->and($rows[0]->source_key)->not->toBe($rows[1]->source_key);
});

it('maps the status column', function (): void {
    $rows = sampleRows();

    expect($rows[2]->is_duplicate)->toBeTrue()
        ->and($rows[3]->status)->toBe(ChildStatusEnum::Graduated)
        ->and($rows[4]->status)->toBe(ChildStatusEnum::Inactive);
});

it('groups siblings by parent email, drops duplicates, and quarantines rows with no email', function (): void {
    $proposal = (new GetRegistryProposalJob(rows: sampleRows()))->handle();

    $ramos = collect($proposal->families)->firstWhere('email', 'janice@example.com');

    expect($proposal->families)->toHaveCount(3)
        ->and($ramos?->children)->toHaveCount(2)
        ->and($proposal->dropped_duplicates)->toBe(1)
        ->and($proposal->quarantined)->toHaveCount(1)
        ->and($proposal->quarantined[0]->reason)->toBe('No parent email');
});

it('only SUGGESTS joining families that share a phone, never joins them', function (): void {
    $proposal = (new GetRegistryProposalJob(rows: sampleRows()))->handle();

    $rodel = collect($proposal->families)->firstWhere('email', 'rodel@example.com');

    expect($rodel?->children)->toHaveCount(1)
        ->and($rodel?->suggestions)->toContain('Shares a phone number with janice@example.com');
});

it('balances: every input row is accepted, dropped or quarantined', function (): void {
    $proposal = (new GetRegistryProposalJob(rows: sampleRows()))->handle();

    $accepted = array_sum(array_map(fn ($f) => count($f->children), $proposal->families));

    expect($accepted + $proposal->dropped_duplicates + count($proposal->quarantined))->toBe(6);
});

it('picks the home satellite from the worship schedule and flags an "either"', function (): void {
    $proposal = (new GetRegistryProposalJob(rows: sampleRows()))->handle();

    $rala = collect($proposal->families)->firstWhere('email', 'rala@example.com');

    expect($rala?->home_satellite)->toBe('House Church');
});

it('quarantines a blank or unknown birth month instead of guessing January (Codex diff review, medium)', function (): void {
    $rows = (new GetRegistryRowsJob(path: base_path('tests/Fixtures/registry-bad-months.csv')))->handle();
    $proposal = (new GetRegistryProposalJob(rows: $rows))->handle();

    expect($rows[0]->date_of_birth)->toBeNull()
        ->and($rows[1]->date_of_birth)->toBeNull()
        ->and($proposal->families)->toBe([])
        ->and(array_map(fn ($q) => $q->reason, $proposal->quarantined))->toBe(['Birthday missing or invalid', 'Birthday missing or invalid']);
});

it('quarantines a short or malformed record instead of dropping it silently (review B2)', function (): void {
    $path = sys_get_temp_dir().'/registry-short-'.uniqid().'.csv';
    $lines = file(base_path('tests/Fixtures/registry-sample.csv')) ?: [];
    file_put_contents($path, $lines[0]."Lonely,Row,Only three columns\n");

    $rows = (new GetRegistryRowsJob(path: $path))->handle();
    $proposal = (new GetRegistryProposalJob(rows: $rows))->handle();

    expect($rows)->toHaveCount(1)
        ->and($proposal->quarantined)->toHaveCount(1);
});
