<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change a family's OTHER guardians as an edit form says (DECISIONS 2026-10-17, after first look): a row with an id
 * changes that guardian, a row without one adds a guardian (no login), and an id in `removedIds` removes that one.
 * A guardian the form does not mention is left alone, so a page opened before someone else added one cannot remove
 * it. Each change is noted on the family's history.
 *
 * Only guardians with no login, other than the primary, can be changed here. Every id is checked before anything is
 * written: the primary, a guardian with a login, another family's — or any guardian of a family with no primary —
 * changes nothing and is a 404 (ModelNotFoundException). A removal of a guardian already gone is passed over.
 *
 * Answers whether a phone came in or changed — the duplicate check need not run otherwise.
 *
 * @extends BaseJob<bool>
 */
final class UpdateFamilyGuardiansJob extends BaseJob
{
    /**
     * @param  list<GuardianEditData>  $guardians  the rows the form sends: changed or new
     * @param  list<int>  $removedIds
     */
    public function __construct(
        private readonly Family $family,
        private readonly array $guardians,
        private readonly array $removedIds,
        private readonly ?User $by,
    ) {}

    public function handle(): bool
    {
        if ($this->family->primary_guardian_id === null) {
            throw (new ModelNotFoundException)->setModel(Guardian::class);
        }

        /** @var Collection<int, Guardian> $editable */
        $editable = $this->family->guardians()->whereKeyNot($this->family->primary_guardian_id)->whereNull('user_id')->get()->keyBy('id');
        $editedIds = array_filter(array_map(fn (GuardianEditData $g): ?int => $g->id, $this->guardians));
        // A removal of a guardian no longer there (someone else removed it) is passed over; any other is checked.
        /** @var list<int> $stillThere */
        $stillThere = Guardian::query()->where('church_id', $this->family->church_id)->whereKey($this->removedIds)->pluck('id')->all();
        foreach ([...$editedIds, ...$stillThere] as $id) {
            if (! $editable->has($id)) {
                throw (new ModelNotFoundException)->setModel(Guardian::class, [$id]);
            }
        }

        $phones = false;
        foreach ($this->guardians as $row) {
            $guardian = $row->id !== null ? $editable->get($row->id) : null;
            $phones = $this->save($guardian ?? new Guardian(['church_id' => $this->family->church_id, 'family_id' => $this->family->id, 'can_pick_up' => true]), $row) || $phones;
        }

        foreach ($editable->only($this->removedIds) as $gone) {
            $gone->delete();
            $this->note("{$this->who($gone)} removed from the family.");
        }

        return $phones;
    }

    /** @return bool whether a phone came in or changed */
    private function save(Guardian $guardian, GuardianEditData $row): bool
    {
        $isNew = ! $guardian->exists;
        $guardian->fill([
            'first_name' => $row->first_name,
            'last_name' => $row->last_name,
            'relationship' => $row->relationship,
            'phone' => $row->phone,
            'ministries' => $row->ministries,
        ]);
        if (! $guardian->isDirty()) {
            return false;
        }

        $phone = $isNew || $guardian->isDirty('phone');
        $guardian->save();
        $this->note($isNew ? "{$this->who($guardian)} added to the family." : "{$this->who($guardian)}: details changed.");

        return $phone;
    }

    private function who(Guardian $guardian): string
    {
        return "{$guardian->first_name} {$guardian->last_name} ({$guardian->relationship->label()})";
    }

    private function note(string $body): void
    {
        Note::system(about: $this->family, body: $body, by: $this->by);
    }
}
