<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use App\Foundation\Support\TextRow;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The print list (name cards 04, Roy 2026-10-29) as an Excel file in the shape of the sheet's Barcode tab, so today's
 * printing keeps working: Full name, Nickname, Level, Squad, Barcode — one row per card, in the order given. The
 * Barcode is the child's stored `name_key`, the very code the door matches a scanned card against (NameKey); the
 * Squad is the one the child's Level and gender put them in (LevelChart), empty with Level for a child with no Level.
 * Every cell plain text (TextRow). Writing it moves no request.
 *
 * @extends BaseJob<string>
 */
final class CreateCardPrintListSpreadsheetJob extends BaseJob
{
    /** @param list<CardRequest> $requests with their child loaded */
    public function __construct(private readonly array $requests) {}

    /** @return string path to the written file */
    public function handle(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nxtgen-print-list-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(TextRow::of(['Full name', 'Nickname', 'Level', 'Squad', 'Barcode']));

        foreach ($this->requests as $request) {
            $child = $request->child;
            $level = $child->level;
            $writer->addRow(TextRow::of([
                $child->fullName(),
                $child->nickname,
                $level === null ? '' : LevelChart::name($level),
                $level === null ? '' : LevelChart::squadName($level, LevelChart::squadGender($level, $child->gender)),
                $child->name_key,
            ]));
        }

        $writer->close();

        return $path;
    }
}
