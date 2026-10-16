<?php

declare(strict_types=1);

use App\Domains\Registry\DataObjects\RegistryProposalData;
use App\Domains\Registry\Jobs\CreateRegistryReviewSpreadsheetJob;
use App\Domains\Registry\Jobs\GetRegistryProposalJob;
use App\Domains\Registry\Jobs\GetRegistryRowsJob;
use OpenSpout\Reader\XLSX\Reader;

/* The review file a person checks before loading: families, quarantine, and a reconciliation that must balance. */

/** @return array<string, list<list<string>>> sheet name => rows, every cell as text */
function reviewSheets(RegistryProposalData $proposal, int $inputRows): array
{
    $path = (new CreateRegistryReviewSpreadsheetJob(proposal: $proposal, path: sys_get_temp_dir().'/review-'.uniqid().'.xlsx', inputRows: $inputRows))->handle();
    $reader = new Reader;
    $reader->open($path);
    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheets[$sheet->getName()][] = array_values(array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $row->toArray()));
        }
    }
    $reader->close();

    return $sheets;
}

function sampleReviewProposal(): RegistryProposalData
{
    return (new GetRegistryProposalJob(rows: (new GetRegistryRowsJob(path: base_path('tests/Fixtures/registry-sample.csv')))->handle()))->handle();
}

it('writes one approvable row per proposed child, under the review columns', function (): void {
    $families = reviewSheets(sampleReviewProposal(), 6)['Families'];
    $header = $families[0];
    $alexis = array_combine($header, array_pad($families[1], count($header), ''));

    expect($header)->toBe(CreateRegistryReviewSpreadsheetJob::COLUMNS)
        ->and(count($families) - 1)->toBe(4)
        ->and($alexis['Approve'])->toBe('yes')
        ->and($alexis['Parent email'])->toBe('janice@example.com')
        ->and($alexis['Child first name'])->toBe('Alexis')
        ->and($alexis['Gender'])->toBe('girl')
        ->and($alexis['Birthday'])->toBe('2019-05-10')
        ->and($alexis['Home satellite'])->toBe('CCF DXB');
});

it('lists quarantined rows with the reason, and reconciles to the sheet\'s row count', function (): void {
    $sheets = reviewSheets(sampleReviewProposal(), 6);
    $reconciliation = array_column($sheets['Reconciliation'], 1, 0);

    expect($sheets['Quarantine'][1][2])->toBe('No parent email')
        ->and($reconciliation['Rows in the sheet'])->toBe('6')
        ->and($reconciliation['Children proposed for loading'])->toBe('4')
        ->and($reconciliation['Balances'])->toBe('yes');
});

it('says so when the counts do not balance', function (): void {
    $reconciliation = array_column(reviewSheets(sampleReviewProposal(), 7)['Reconciliation'], 1, 0);

    expect($reconciliation['Balances'])->toBe('NO — check before loading');
});
