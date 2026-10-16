<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateChildLevelJob;

/* A church admin's correction of a child's Level outside Moving-Up: the Level, who set it and when, and a history note. */

it('puts the child at the Level, recording who and when', function (): void {
    $child = Child::factory()->create(['level' => 1]);
    $admin = User::factory()->create();

    $updated = (new UpdateChildLevelJob(child: $child, level: 2, by: $admin))->handle();

    $child->refresh();
    expect($updated->level)->toBe(2)
        ->and($child->level)->toBe(2)
        ->and($child->level_set_by)->toBe($admin->id)
        ->and($child->level_set_at)->not->toBeNull();
});

it('notes the change on the child\'s history, from the Level they had', function (?int $from, string $fromName): void {
    $child = Child::factory()->create(['nickname' => 'Amara', 'level' => $from]);
    $admin = User::factory()->create();

    (new UpdateChildLevelJob(child: $child, level: 2, by: $admin))->handle();

    $note = Note::query()->sole();
    expect([$note->noteable_type, $note->noteable_id, $note->author_id])->toBe(['child', $child->id, $admin->id])
        ->and($note->body)->toBe("Amara: Level changed from {$fromName} to Level 2.");
})->with([
    'from a Level' => [1, 'Level 1'],
    'from none' => [null, 'no Level'],
]);

it('writes no note when the Level stays the same', function (): void {
    $child = Child::factory()->create(['level' => 2]);

    (new UpdateChildLevelJob(child: $child, level: 2, by: User::factory()->create()))->handle();

    expect(Note::query()->count())->toBe(0);
});
