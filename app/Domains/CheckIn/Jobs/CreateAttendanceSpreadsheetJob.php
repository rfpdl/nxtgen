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
 * no health detail, phone or photo — and every cell plain text (TextRow). The roll call (DECISIONS 2026-10-28): Present
 * ("Yes" or empty) and the Squad's head count (the number or empty, on each row of that Squad; a counted Squad with
 * no one in now has one row of its own).
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
        $writer->addRow(TextRow::of(['Squad', 'Child nickname', 'Child full name', 'Checked in at', 'Checked in by', 'Present', 'Squad head count']));

        foreach ($this->squads as $squad) {
            $headCount = $squad->head_count === null ? '' : (string) $squad->head_count->count;
            if ($squad->children === []) {
                // Counted, but no one in now: one row with its head count.
                $writer->addRow(TextRow::of([$squad->name, '', '', '', '', '', $headCount]));
            }
            foreach ($squad->children as $child) {
                $writer->addRow(TextRow::of([$squad->name, $child->nickname, $child->full_name, $child->checked_in_at, $child->checked_in_by, $child->present ? 'Yes' : '', $headCount]));
            }
        }

        $writer->close();

        return $path;
    }
}
