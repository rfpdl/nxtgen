<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Domains\Family\DataObjects\NoteData;
use App\Enums\NoteVisibilityEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;

/**
 * Staff notes on a family AND its children, newest first — the family page's history.
 *
 * @extends BaseJob<list<NoteData>>
 */
final class GetFamilyNotesJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    /** @return list<NoteData> */
    public function handle(): array
    {
        $childIds = $this->family->children()->pluck('id')->all();

        return array_values(Note::query()
            ->with('author')
            ->where('church_id', $this->family->church_id)
            ->where('visibility', NoteVisibilityEnum::Staff)
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $f) => $f->where('noteable_type', 'family')->where('noteable_id', $this->family->id))
                ->orWhere(fn (Builder $c) => $c->where('noteable_type', 'child')->whereIn('noteable_id', $childIds)))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Note $n): NoteData => NoteData::fromModel($n))
            ->all());
    }
}
