<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Foundation\Jobs\BaseJob;

/**
 * The details a parent may change. Status and Level are deliberately NOT here: status is staff's, and the
 * Level changes only at Moving-Up (plan §0).
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildDetailsJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly ChildDetailsData $details,
    ) {}

    public function handle(): Child
    {
        $d = $this->details;

        $this->child->update([
            'first_name' => trim($d->firstName),
            'middle_name' => self::clean($d->middleName),
            'last_name' => trim($d->lastName),
            'nickname' => trim($d->nickname) !== '' ? trim($d->nickname) : trim($d->firstName),
            'gender' => $d->gender,
            'date_of_birth' => $d->dateOfBirth,
            'allergies' => self::clean($d->allergies),
            'illnesses' => self::clean($d->illnesses),
            'dislikes' => self::clean($d->dislikes),
            'fears' => self::clean($d->fears),
            'guardian_notes' => self::clean($d->guardianNotes),
        ]);

        return $this->child;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
