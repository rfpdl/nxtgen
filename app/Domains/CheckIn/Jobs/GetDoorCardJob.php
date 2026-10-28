<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Domains\CheckIn\DataObjects\DoorCardData;
use App\Domains\CheckIn\DataObjects\DoorScanData;
use App\Enums\CardMatchEnum;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\NameKey;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * A door search read as a name card (slice 3): a search the camera read (`card`), or one word of letters a desk
 * scanner typed — anything else is an ordinary search (null), and so is an empty or digits-only camera read.
 *
 * - Exact: children of this church whose full name, as a key (NameKey), IS the code. One → ticked; several → their
 *   families, nobody ticked ("tick the right one").
 * - Else, for a code of six letters or more, close: a child whose middle name is missing on ONE side only — the code
 *   is the child's first + last name (the card has no middle name), or the child has none and the code starts with
 *   their first name and ends with their last. Never two different middle names (a cousin), never fuzzier. Shown,
 *   never ticked ("check this is the right child").
 * - Else none: "No child matches this card". A typed card then runs the ordinary search too (it may be a surname).
 *
 * @extends BaseJob<DoorCardData|null>
 */
final class GetDoorCardJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $query,
        private readonly bool $card,
    ) {}

    public function handle(): ?DoorCardData
    {
        $code = NameKey::code($this->query);
        if (strlen($code) < 2 || (! $this->card && ! NameKey::looksLikeCode($this->query))) {
            return null;
        }

        $exact = $this->children(fn (Builder $q) => $q->where('name_key', $code));
        if ($exact !== []) {
            return $this->read($code, count($exact) === 1 ? CardMatchEnum::Exact : CardMatchEnum::Several, $exact);
        }

        $close = strlen($code) < NameKey::CLOSE_MATCH_MIN_LETTERS ? [] : $this->children(fn (Builder $q) => $q
            ->where(fn (Builder $one) => $one
                // The card has no middle name; the child has one.
                ->where(fn (Builder $w) => $w
                    ->whereRaw('name_key != CONCAT(name_key_first, name_key_last)')
                    ->whereRaw('CONCAT(name_key_first, name_key_last) = ?', [$code]))
                // The child has no middle name; the card has one.
                ->orWhere(fn (Builder $w) => $w
                    ->whereRaw('name_key = CONCAT(name_key_first, name_key_last)')
                    ->whereRaw('CHAR_LENGTH(?) > CHAR_LENGTH(name_key)', [$code])
                    ->whereRaw("? LIKE CONCAT(name_key_first, '%', name_key_last)", [$code]))));

        return $this->read($code, $close !== [] ? CardMatchEnum::Close : CardMatchEnum::None, $close);
    }

    /** @param list<Child> $children */
    private function read(string $code, CardMatchEnum $match, array $children): DoorCardData
    {
        $families = new Collection(collect($children)->map(fn (Child $c): Family => $c->family)->unique('id')->values()->all());

        return new DoorCardData(
            families: $families,
            scan: DoorScanData::of($code, $match, array_map(fn (Child $c): int => $c->id, $children)),
            searchToo: $match === CardMatchEnum::None && ! $this->card,
        );
    }

    /**
     * This church's children the condition picks, each with its family as the door shows it.
     *
     * @param  Closure(Builder<Child>): mixed  $condition
     * @return list<Child>
     */
    private function children(Closure $condition): array
    {
        return array_values(Child::query()
            ->where('church_id', $this->church->id)
            // A name with no letters in its first or last part spells nothing a card could.
            ->where('name_key_first', '!=', '')
            ->where('name_key_last', '!=', '')
            ->where(fn (Builder $q) => $condition($q))
            ->with(['family.primaryGuardian.user', 'family.homeSatellite', 'family.children'])
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->all());
    }
}
