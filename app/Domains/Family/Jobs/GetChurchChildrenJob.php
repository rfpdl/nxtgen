<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Several children by id, ONLY within this church (any family, any satellite) — the ones ticked at the door, keyed by
 * id in the order asked. One id that is not this church's child refuses them all: the request was not made from a
 * page this church showed.
 *
 * @extends BaseJob<array<int, Child>>
 */
final class GetChurchChildrenJob extends BaseJob
{
    /** @param list<int> $ids */
    public function __construct(
        private readonly Church $church,
        private readonly array $ids,
    ) {}

    /** @return array<int, Child> */
    public function handle(): array
    {
        $ids = array_values(array_unique($this->ids));
        $found = Child::query()->where('church_id', $this->church->id)->whereIn('id', $ids)->get()->keyBy('id');

        if ($found->count() !== count($ids)) {
            throw (new ModelNotFoundException)->setModel(Child::class, array_values(array_diff($ids, $found->keys()->all())));
        }

        $children = [];
        foreach ($ids as $id) {
            $child = $found->get($id);
            if ($child instanceof Child) {
                $children[$id] = $child;
            }
        }

        return $children;
    }
}
