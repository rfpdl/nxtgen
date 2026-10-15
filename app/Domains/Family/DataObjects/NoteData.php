<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Note;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One note on a family's staff page: what, who ("NxtGen" when the app wrote it with no person), when. */
#[TypeScript]
final class NoteData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public string $author,
        public string $at,
    ) {}

    public static function fromModel(Note $note): self
    {
        return new self(
            id: $note->id,
            body: $note->body,
            author: $note->author !== null ? $note->author->name : 'NxtGen',
            at: $note->created_at?->toDateTimeString() ?? '',
        );
    }
}
