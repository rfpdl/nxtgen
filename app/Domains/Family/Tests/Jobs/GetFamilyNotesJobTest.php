<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\GetFamilyNotesJob;
use App\Enums\NoteVisibilityEnum;
use Illuminate\Support\Carbon;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A family page's history: staff notes on the family and its children, newest first — no one else's. */

it('lists staff notes on the family and its children, newest first, naming the author', function (): void {
    $family = familyAt(satelliteOf(church()));
    $child = childOf($family);
    $author = User::factory()->create(['name' => 'Ate Joy']);
    Carbon::setTestNow('2026-10-01 10:00:00');
    Note::factory()->for($family, 'noteable')->create(['church_id' => $family->church_id, 'body' => 'Older, on the family']);
    Carbon::setTestNow('2026-10-02 10:00:00');
    Note::factory()->for($child, 'noteable')->create(['church_id' => $family->church_id, 'body' => 'Newer, on the child', 'author_id' => $author->id]);

    $notes = (new GetFamilyNotesJob(family: $family))->handle();

    expect(array_map(fn ($n): array => [$n->body, $n->author], $notes))->toBe([
        ['Newer, on the child', 'Ate Joy'],
        ['Older, on the family', 'NxtGen'],
    ]);
});

it('leaves out another family\'s notes and notes not for staff', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $neighbour = familyAt($satellite);
    Note::factory()->for($neighbour, 'noteable')->create(['church_id' => $neighbour->church_id]);
    Note::factory()->for(childOf($neighbour), 'noteable')->create(['church_id' => $neighbour->church_id]);
    Note::factory()->for($family, 'noteable')->create(['church_id' => $family->church_id, 'visibility' => NoteVisibilityEnum::Family]);

    expect((new GetFamilyNotesJob(family: $family))->handle())->toBe([]);
});
