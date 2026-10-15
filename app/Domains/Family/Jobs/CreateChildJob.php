<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Enums\ChildStatusEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Children are part of the family aggregate, so the Family domain creates them.
 *
 * @extends BaseJob<Child>
 */
final class CreateChildJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly ChildDetailsData $details,
        private readonly ?Group $group,
        private readonly RegistrationSourceEnum $source,
    ) {}

    public function handle(): Child
    {
        $d = $this->details;

        return Child::query()->create([
            'organization_id' => $this->family->organization_id,
            'family_id' => $this->family->id,
            'group_id' => $this->group?->id,
            'group_set_at' => $this->group !== null ? now() : null,
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
            'parent_notes' => self::clean($d->parentNotes),
            'status' => $d->status ?? ChildStatusEnum::Active,
            'status_changed_at' => now(),
            'registration_source' => $this->source,
        ]);
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
