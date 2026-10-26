<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\TextRow;
use OpenSpout\Writer\XLSX\Writer;

/**
 * A Session's Attendance as an Excel file (DECISIONS 2026-10-26: reports are admins only, with Excel): the page's own
 * rows (AttendanceSquadData), one per child under its Squad — the same values, never rebuilt. Allowlisted columns —
 * no health detail, phone or photo — and every cell plain text (TextRow).
 *
 * @extends BaseJob<string>
 */
final class CreateAttendanceSpreadsheetJob extends BaseJob
{
    /** @param list<AttendanceSquadData> $squads */
    public function __construct(private readonly array $squads) {}

    /** @return string path to the written file */
    public function handle(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nxtgen-attendance-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(TextRow::of(['Squad', 'Child nickname', 'Child full name', 'Checked in at', 'Checked in by']));

        foreach ($this->squads as $squad) {
            foreach ($squad->children as $child) {
                $writer->addRow(TextRow::of([$squad->name, $child->nickname, $child->full_name, $child->checked_in_at, $child->checked_in_by]));
            }
        }

        $writer->close();

        return $path;
    }
}
