<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Change a child's photo consent AND note it on the child's history — every change, by everyone (review 4): the
 * parent agreeing (alone or with the first photo), a volunteer recording the parent's in-person yes (Roy
 * 2026-10-16), and the parent withdrawing (which hides the photo). The caller checks who may.
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildPhotoConsentJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly bool $given,
        private readonly User $by,
        private readonly bool $inPerson = false,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill([
            'photo_consent_at' => $this->given ? now() : null,
            'photo_consent_by' => $this->given ? $this->by->id : null,
        ])->save();

        $name = $this->child->displayName();
        $body = match (true) {
            ! $this->given => "{$name}: photo consent withdrawn by {$this->by->name} — the photo is hidden.",
            $this->inPerson => "{$name}: photo consent recorded by {$this->by->name} — the parent said yes in person.",
            default => "{$name}: photo consent given by {$this->by->name}.",
        };
        Note::system(about: $this->child, body: $body, by: $this->by);

        return $this->child;
    }
}
