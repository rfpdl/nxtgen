<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Enums\NoteKindEnum;
use App\Enums\NoteVisibilityEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Record what staff changed on a family or a child (plan §4.4 "audited as a system note"). Staff-only; the
 * parent never sees these.
 *
 * @extends BaseJob<Note>
 */
final class CreateSystemNoteJob extends BaseJob
{
    public function __construct(
        private readonly Family|Child $about,
        private readonly string $body,
        private readonly ?User $by,
    ) {}

    public function handle(): Note
    {
        return Note::query()->create([
            'organization_id' => $this->about->organization_id,
            'noteable_type' => $this->about->getMorphClass(),
            'noteable_id' => $this->about->id,
            'author_id' => $this->by?->id,
            'kind' => NoteKindEnum::System,
            'body' => $this->body,
            'visibility' => NoteVisibilityEnum::Staff,
        ]);
    }
}
