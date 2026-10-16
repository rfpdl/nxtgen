<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\User;
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
        return Note::system(about: $this->about, body: $this->body, by: $this->by);
    }
}
