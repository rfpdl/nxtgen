<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\ApprovedChildRowData;
use App\Domains\Registry\DataObjects\RegistryApprovalData;
use App\Enums\GenderEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Read the REVIEWED spreadsheet's "Families" sheet and keep the rows marked Approve = yes, grouped by the
 * (possibly edited) family key. A missing sheet or an approved row without a family key is reported as a
 * problem, never dropped silently (review-spar r2).
 *
 * @extends BaseJob<RegistryApprovalData>
 */
final class GetApprovedRegistryRowsJob extends BaseJob
{
    public function __construct(private readonly string $path) {}

    public function handle(): RegistryApprovalData
    {
        $reader = new Reader;
        $reader->open($this->path);
        $rowsByFamily = [];
        $problems = [];
        $foundSheet = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() !== 'Families') {
                continue;
            }
            $foundSheet = true;
            $header = null;
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $row->toArray());
                if ($header === null) {
                    $header = $cells;

                    continue;
                }
                $values = [];
                foreach ($header as $i => $name) {
                    $values[$name] = $cells[$i] ?? '';
                }
                $approved = new ApprovedChildRowData($values);
                if (! $approved->yes('Approve')) {
                    continue;
                }
                $invalid = $this->invalid($approved);
                if ($invalid !== null) {
                    $problems[] = "Sheet row {$approved->get('Sheet row')}: {$invalid} — not loaded";

                    continue;
                }
                $rowsByFamily[$approved->get('Family key')][] = $approved;
            }
        }

        $reader->close();

        if (! $foundSheet) {
            $problems[] = 'The review file has no "Families" sheet — nothing loaded';
        }

        return new RegistryApprovalData(rowsByFamily: $rowsByFamily, problems: $problems);
    }

    /**
     * The review file is EDITED by a person, so identity fields are validated again here — the CSV checks cannot
     * protect later edits. A cleared parent email would otherwise merge households under one blank login
     * (review-spar r3).
     */
    private function invalid(ApprovedChildRowData $row): ?string
    {
        $email = mb_strtolower($row->get('Parent email'));
        $birthday = $row->get('Birthday');

        return match (true) {
            $row->get('Family key') === '' => 'approved but has no Family key',
            filter_var($email, FILTER_VALIDATE_EMAIL) === false => 'parent email is missing or invalid',
            $row->get('Child first name') === '' || $row->get('Child last name') === '' => 'child name is missing',
            GenderEnum::tryFrom($row->get('Gender')) === null => 'boy or girl is missing or invalid',
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday) !== 1 || ! checkdate((int) Str::substr($birthday, 5, 2), (int) Str::substr($birthday, 8, 2), (int) Str::substr($birthday, 0, 4)) => 'birthday is missing or invalid',
            $row->get('Home satellite') === '' => 'home satellite is missing',
            default => null,
        };
    }
}
