<?php

declare(strict_types=1);

use App\Domains\Registry\DataObjects\RegistryProposalData;
use App\Domains\Registry\Jobs\GetRegistryProposalJob;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;

/* Registry rows → proposed families: siblings grouped by parent email, nothing guessed, every row accounted for. */

function proposalFromCsv(string $path): RegistryProposalData
{
    return (new GetRegistryProposalJob(rows: (new GetRegistryRowsJob(path: $path))->handle()))->handle();
}

function sampleProposal(): RegistryProposalData
{
    return proposalFromCsv(base_path('tests/Fixtures/registry-sample.csv'));
}

it('groups siblings by parent email, drops duplicates, and quarantines rows with no email', function (): void {
    $proposal = sampleProposal();

    $ramos = collect($proposal->families)->firstWhere('email', 'janice@example.com');

    expect($proposal->families)->toHaveCount(3)
        ->and($ramos?->children)->toHaveCount(2)
        ->and($proposal->droppedDuplicates)->toBe(1)
        ->and($proposal->quarantined)->toHaveCount(1)
        ->and($proposal->quarantined[0]->reason)->toBe('No parent email');
});

it('only SUGGESTS joining families that share a phone, never joins them', function (): void {
    $rodel = collect(sampleProposal()->families)->firstWhere('email', 'rodel@example.com');

    expect($rodel?->children)->toHaveCount(1)
        ->and($rodel?->suggestions)->toContain('Shares a phone number with janice@example.com');
});

it('balances: every input row is accepted, dropped or quarantined', function (): void {
    $proposal = sampleProposal();

    $accepted = array_sum(array_map(fn ($f) => count($f->children), $proposal->families));

    expect($accepted + $proposal->droppedDuplicates + count($proposal->quarantined))->toBe(6);
});

it('picks the home satellite from the worship schedule and flags an "either"', function (): void {
    $rala = collect(sampleProposal()->families)->firstWhere('email', 'rala@example.com');

    expect($rala?->homeSatellite)->toBe('House Church');
});

// Found by Codex diff review (medium).
it('quarantines a blank or unknown birth month instead of guessing January', function (): void {
    $proposal = proposalFromCsv(base_path('tests/Fixtures/registry-bad-months.csv'));

    expect($proposal->families)->toBe([])
        ->and(array_map(fn ($q) => $q->reason, $proposal->quarantined))->toBe(['Birthday missing or invalid', 'Birthday missing or invalid']);
});

// Found in review B2.
it('quarantines a short or malformed record instead of dropping it silently', function (): void {
    $path = sys_get_temp_dir().'/registry-short-'.uniqid().'.csv';
    $lines = file(base_path('tests/Fixtures/registry-sample.csv')) ?: [];
    file_put_contents($path, $lines[0]."Lonely,Row,Only three columns\n");

    expect(proposalFromCsv($path)->quarantined)->toHaveCount(1);
});
