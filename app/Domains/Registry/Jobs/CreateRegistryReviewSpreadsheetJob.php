<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\RegistryProposalData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\TextRow;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The human review step (plan §7): one row per child with its proposed family. The reviewer may set
 * "Approve" to no, change "Family key" to regroup, fix "Home satellite", or write a child's "Level" ("Level 2", "Level 2
 * Boys" or "2") where the age should not decide it. Quarantined rows and a
 * reconciliation that must balance are on their own sheets.
 *
 * @extends BaseJob<string>
 */
final class CreateRegistryReviewSpreadsheetJob extends BaseJob
{
    public const array COLUMNS = [
        'Approve', 'Family key', 'Parent email', 'Parent first name', 'Parent last name', 'Relationship', 'Phone',
        'Home satellite', 'Area', 'FB group', 'FB account', 'In DGroup', 'DGroup area', 'DGroup leader',
        'DGroup leader phone', 'Wants DGroup', 'Ministry', 'Lifestage', 'Privacy consented', 'Source key', 'Sheet row',
        'Child first name', 'Child last name', 'Nickname', 'Gender', 'Birthday', 'Allergies', 'Dislikes', 'Fears',
        'Notes', 'Status', 'Check this', 'Distinct child', 'Level',
    ];

    public function __construct(
        private readonly RegistryProposalData $proposal,
        private readonly string $path,
        private readonly int $inputRows,
    ) {}

    public function handle(): string
    {
        $yes = static fn (bool $v): string => $v ? 'yes' : 'no';
        $writer = new Writer;
        $writer->openToFile($this->path);
        $writer->getCurrentSheet()->setName('Families');
        $writer->addRow(TextRow::of(self::COLUMNS));

        $accepted = 0;
        foreach ($this->proposal->families as $family) {
            $p = $family->primary;
            foreach ($family->children as $child) {
                $accepted++;
                $writer->addRow(TextRow::of([
                    'yes', $family->key, $family->email, $p->guardian_first_name, $p->guardian_last_name, $p->relationship->value, $p->phone ?? '',
                    $family->home_satellite, $p->area ?? '', $yes($p->wants_fb_group), $p->fb_account ?? '', $yes($p->in_dgroup), $p->dgroup_area ?? '',
                    $p->dgroup_leader_name ?? '', $p->dgroup_leader_phone ?? '', $yes($p->wants_dgroup), $p->ministry ?? '', $p->lifestage ?? '',
                    $yes($p->privacy_consented), $child->source_key, (string) $child->row_number,
                    $child->child_first_name, $child->child_last_name, $child->nickname, $child->gender->value ?? '', $child->date_of_birth ?? '',
                    $child->allergies ?? '', $child->dislikes ?? '', $child->fears ?? '', $child->guardian_notes ?? '', $child->status->value,
                    implode('; ', $family->suggestions),
                    '',
                    '',
                ]));
            }
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Quarantine');
        $writer->addRow(TextRow::of(['Sheet row', 'Child', 'Why it was not loaded']));
        foreach ($this->proposal->quarantined as $q) {
            $writer->addRow(TextRow::of([(string) $q->row_number, $q->child_name, $q->reason]));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Reconciliation');
        $quarantined = count($this->proposal->quarantined);
        foreach ([
            ['Rows in the sheet', (string) $this->inputRows],
            ['Children proposed for loading', (string) $accepted],
            ['Duplicates dropped', (string) $this->proposal->dropped_duplicates],
            ['Quarantined', (string) $quarantined],
            ['Balances', $accepted + $this->proposal->dropped_duplicates + $quarantined === $this->inputRows ? 'yes' : 'NO — check before loading'],
            ['Families proposed', (string) count($this->proposal->families)],
        ] as $line) {
            $writer->addRow(TextRow::of($line));
        }

        $writer->close();

        return $this->path;
    }
}
