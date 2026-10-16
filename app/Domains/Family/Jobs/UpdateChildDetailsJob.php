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
            'first_name' => trim($d->first_name),
            'middle_name' => self::clean($d->middle_name),
            'last_name' => trim($d->last_name),
            'nickname' => trim($d->nickname) !== '' ? trim($d->nickname) : trim($d->first_name),
            'gender' => $d->gender,
            'date_of_birth' => $d->date_of_birth,
            'allergies' => self::clean($d->allergies),
            'illnesses' => self::clean($d->illnesses),
            'dislikes' => self::clean($d->dislikes),
            'fears' => self::clean($d->fears),
            'guardian_notes' => self::clean($d->guardian_notes),
        ]);

        return $this->child;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
