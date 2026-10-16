<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Enums\ChildStatusEnum;
use App\Enums\NoteKindEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child's status (active / inactive / graduated), who changed it and when — and, when it changed, a history note. */

it('sets the status, records who and when, and notes the change on the child', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara', 'status' => ChildStatusEnum::Active]);
    $admin = User::factory()->create();

    $updated = (new UpdateChildStatusJob(child: $child, status: ChildStatusEnum::Inactive, by: $admin))->handle();

    $child->refresh();
    $note = Note::query()->sole();
    expect($updated->status)->toBe(ChildStatusEnum::Inactive)
        ->and($child->status_changed_by)->toBe($admin->id)
        ->and($child->status_changed_at)->not->toBeNull()
        ->and([$note->noteable_type, $note->noteable_id, $note->kind, $note->author_id, $note->church_id])
        ->toBe(['child', $child->id, NoteKindEnum::System, $admin->id, $child->church_id])
        ->and($note->body)->toBe('Amara: status changed from Active to Inactive.');
});

it('writes no note when the status stays the same', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['status' => ChildStatusEnum::Active]);

    (new UpdateChildStatusJob(child: $child, status: ChildStatusEnum::Active, by: User::factory()->create()))->handle();

    expect(Note::query()->count())->toBe(0);
});
