<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\RegistryProposalData;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\TextRow;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The human review step (plan §7): one row per child with its proposed family. The reviewer may set
 * "Approve" to no, change "Family key" to regroup, or fix "Home satellite". Quarantined rows and a
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
        'Notes', 'Status', 'Check this', 'Distinct child', 'Group',
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
                    'yes', $family->key, $family->email, $p->parentFirstName, $p->parentLastName, $p->relationship->value, $p->phone ?? '',
                    $family->homeSatellite, $p->area ?? '', $yes($p->wantsFbGroup), $p->fbAccount ?? '', $yes($p->inDgroup), $p->dgroupArea ?? '',
                    $p->dgroupLeaderName ?? '', $p->dgroupLeaderPhone ?? '', $yes($p->wantsDgroup), $p->ministry ?? '', $p->lifestage ?? '',
                    $yes($p->privacyConsented), $child->sourceKey, (string) $child->rowNumber,
                    $child->childFirstName, $child->childLastName, $child->nickname, $child->gender->value ?? '', $child->dateOfBirth ?? '',
                    $child->allergies ?? '', $child->dislikes ?? '', $child->fears ?? '', $child->parentNotes ?? '', $child->status->value,
                    implode('; ', $family->suggestions),
                    '',
                    '',
                ]));
            }
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Quarantine');
        $writer->addRow(TextRow::of(['Sheet row', 'Child', 'Why it was not loaded']));
        foreach ($this->proposal->quarantined as $q) {
            $writer->addRow(TextRow::of([(string) $q->rowNumber, $q->childName, $q->reason]));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Reconciliation');
        $quarantined = count($this->proposal->quarantined);
        foreach ([
            ['Rows in the sheet', (string) $this->inputRows],
            ['Children proposed for loading', (string) $accepted],
            ['Duplicates dropped', (string) $this->proposal->droppedDuplicates],
            ['Quarantined', (string) $quarantined],
            ['Balances', $accepted + $this->proposal->droppedDuplicates + $quarantined === $this->inputRows ? 'yes' : 'NO — check before loading'],
            ['Families proposed', (string) count($this->proposal->families)],
        ] as $line) {
            $writer->addRow(TextRow::of($line));
        }

        $writer->close();

        return $this->path;
    }
}
