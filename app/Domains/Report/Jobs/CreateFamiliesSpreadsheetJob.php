<?php

declare(strict_types=1);

namespace App\Domains\Report\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\EmailMask;
use App\Foundation\Support\TextRow;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The registration list as an Excel file (plan: every list can be downloaded). Allowlisted columns —
 * no health details except allergies; email in full only when the viewer may edit.
 *
 * @extends BaseJob<string>
 */
final class CreateFamiliesSpreadsheetJob extends BaseJob
{
    /** @param Collection<int, Family> $families */
    public function __construct(
        private readonly Collection $families,
        private readonly bool $fullEmail,
    ) {}

    /** @return string path to the written file */
    public function handle(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nxtgen-families-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(TextRow::of(['Family', 'Email', 'Email confirmed', 'Satellite', 'Child nickname', 'Child full name', 'Birthday', 'Level', 'Allergies', 'Status', 'Registered by', 'Registered on']));

        foreach ($this->families as $family) {
            $email = $family->contactEmail();
            $base = [
                $family->contactName(),
                $this->fullEmail ? $email : EmailMask::mask($email),
                $family->contactEmailConfirmed() ? 'Yes' : 'No',
                $family->homeSatellite->name,
            ];

            foreach ($family->children as $child) {
                $writer->addRow(TextRow::of([...$base,
                    $child->nickname,
                    trim("{$child->first_name} {$child->middle_name} {$child->last_name}"),
                    $child->date_of_birth->toDateString(),
                    $child->levelName() ?? '',
                    $child->allergies ?? '',
                    $child->status->label(),
                    $family->registration_source->label(),
                    $family->created_at?->toDateString() ?? '',
                ]));
            }
        }

        $writer->close();

        return $path;
    }
}
