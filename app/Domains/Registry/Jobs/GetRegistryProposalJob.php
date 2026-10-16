<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\ProposedFamilyData;
use App\Domains\Registry\DataObjects\QuarantinedRowData;
use App\Domains\Registry\DataObjects\RegistryProposalData;
use App\Domains\Registry\DataObjects\RegistryRowData;
use App\Foundation\Jobs\BaseJob;

/**
 * Turn registry rows into proposed families (plan §7):
 *   - DUPLICATE rows dropped and counted
 *   - rows without a usable parent email, child name, gender or birthday QUARANTINED (never guessed)
 *   - children grouped by parent email; a phone shared across emails is a SUGGESTION only, never a union
 * Every input row ends up accepted, dropped or quarantined, so the counts reconcile.
 *
 * @extends BaseJob<RegistryProposalData>
 */
final class GetRegistryProposalJob extends BaseJob
{
    /** @param list<RegistryRowData> $rows */
    public function __construct(private readonly array $rows) {}

    public function handle(): RegistryProposalData
    {
        $dropped = 0;
        $quarantined = [];
        /** @var array<string, list<RegistryRowData>> $byEmail */
        $byEmail = [];

        foreach ($this->rows as $row) {
            if ($row->is_duplicate) {
                $dropped++;

                continue;
            }

            $reason = match (true) {
                $row->email === '' => 'No parent email',
                $row->child_first_name === '' || $row->child_last_name === '' => 'Child name missing',
                $row->gender === null => 'Boy or girl missing',
                $row->date_of_birth === null => 'Birthday missing or invalid',
                default => null,
            };

            if ($reason !== null) {
                $quarantined[] = new QuarantinedRowData($row->row_number, trim("{$row->child_first_name} {$row->child_last_name}"), $reason);

                continue;
            }

            $byEmail[$row->email][] = $row;
        }

        $families = [];
        foreach ($byEmail as $email => $children) {
            $primary = $children[0];
            $suggestions = [];

            foreach ($byEmail as $otherEmail => $others) {
                if ($otherEmail !== $email && $primary->phone_key !== '' && $others[0]->phone_key === $primary->phone_key) {
                    $suggestions[] = "Shares a phone number with {$otherEmail}";
                }
            }

            $schedule = mb_strtolower($primary->schedule);
            $onlyHouseChurch = str_contains($schedule, 'house church') && ! str_contains($schedule, 'pm');
            if (str_contains($schedule, 'either') && str_contains($schedule, 'house church')) {
                $suggestions[] = 'Attends CCF DXB or House Church — check the home satellite';
            }

            $families[] = new ProposedFamilyData(
                key: substr(hash('sha256', $email), 0, 16),
                email: $email,
                primary: $primary,
                home_satellite: $onlyHouseChurch ? 'House Church' : 'CCF DXB',
                children: $children,
                suggestions: $suggestions,
            );
        }

        return new RegistryProposalData(families: $families, dropped_duplicates: $dropped, quarantined: $quarantined);
    }
}
