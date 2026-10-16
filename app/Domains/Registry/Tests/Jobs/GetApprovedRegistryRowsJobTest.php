<?php

declare(strict_types=1);

use App\Domains\Registry\Jobs\CreateRegistryReviewSpreadsheetJob;
use App\Domains\Registry\Jobs\GetApprovedRegistryRowsJob;
use App\Foundation\Support\TextRow;
use OpenSpout\Writer\XLSX\Writer;

/* The reviewed file read back: approved rows grouped by family key; anything approved but unloadable is a problem, never dropped. */

/**
 * @param  list<array<string, string>>  $rows  column => value, over a valid approved row
 */
function reviewedFile(array $rows, string $sheet = 'Families'): string
{
    $valid = [
        'Approve' => 'yes', 'Family key' => 'fam-1', 'Parent email' => 'jo@example.com', 'Child first name' => 'Amara',
        'Child last name' => 'Reyes', 'Gender' => 'girl', 'Birthday' => '2019-05-10', 'Home satellite' => 'CCF DXB', 'Sheet row' => '2',
    ];
    $path = sys_get_temp_dir().'/reviewed-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName($sheet);
    $writer->addRow(TextRow::of(CreateRegistryReviewSpreadsheetJob::COLUMNS));
    foreach ($rows as $row) {
        $values = $row + $valid;
        $writer->addRow(TextRow::of(array_map(fn (string $column): string => $values[$column] ?? '', CreateRegistryReviewSpreadsheetJob::COLUMNS)));
    }
    $writer->close();

    return $path;
}

it('keeps the approved rows, grouped by the reviewer\'s family key', function (): void {
    $approval = (new GetApprovedRegistryRowsJob(path: reviewedFile([
        ['Child first name' => 'Amara'],
        ['Child first name' => 'Aaron', 'Approve' => 'Yes'],
        ['Child first name' => 'Ysa', 'Family key' => 'fam-2'],
        ['Child first name' => 'Skipped', 'Approve' => 'no'],
    ])))->handle();

    expect(array_keys($approval->rows_by_family))->toBe(['fam-1', 'fam-2'])
        ->and(array_map(fn ($r) => $r->get('Child first name'), $approval->rows_by_family['fam-1']))->toBe(['Amara', 'Aaron'])
        ->and($approval->problems)->toBe([]);
});

it('reports an approved row it cannot load, naming the sheet row and why', function (string $column, string $value, string $why): void {
    $approval = (new GetApprovedRegistryRowsJob(path: reviewedFile([[$column => $value, 'Sheet row' => '7']])))->handle();

    expect($approval->rows_by_family)->toBe([])
        ->and($approval->problems)->toBe(["Sheet row 7: {$why} — not loaded"]);
})->with([
    'no family key' => ['Family key', '', 'approved but has no Family key'],
    'email cleared' => ['Parent email', '', 'parent email is missing or invalid'],
    'no child name' => ['Child last name', '', 'child name is missing'],
    'unknown gender' => ['Gender', 'x', 'boy or girl is missing or invalid'],
    'impossible birthday' => ['Birthday', '2019-02-30', 'birthday is missing or invalid'],
    'no home satellite' => ['Home satellite', '', 'home satellite is missing'],
]);

it('loads nothing, and says so, when the file has no Families sheet', function (): void {
    $approval = (new GetApprovedRegistryRowsJob(path: reviewedFile([[]], sheet: 'Sheet1')))->handle();

    expect($approval->rows_by_family)->toBe([])
        ->and($approval->problems)->toBe(['The review file has no "Families" sheet — nothing loaded']);
});
