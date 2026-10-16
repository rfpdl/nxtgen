<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\NoteKindEnum;
use Illuminate\Support\Carbon;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child's photo consent — given, recorded in person, or withdrawn — and, every time, a note on the child's history. */

it('records who gave consent and when, and notes it', function (bool $inPerson, string $body): void {
    Carbon::setTestNow('2026-10-17 09:00:00');
    $child = childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara']);
    $by = User::factory()->create(['name' => 'Maria Santos']);

    (new UpdateChildPhotoConsentJob(child: $child, given: true, by: $by, inPerson: $inPerson))->handle();

    $child->refresh();
    $note = Note::query()->sole();
    expect($child->photo_consent_at?->toDateTimeString())->toBe('2026-10-17 09:00:00')
        ->and($child->photo_consent_by)->toBe($by->id)
        ->and([$note->noteable_type, $note->noteable_id, $note->kind, $note->author_id, $note->body])
        ->toBe(['child', $child->id, NoteKindEnum::System, $by->id, $body]);
})->with([
    'by the parent' => [false, 'Amara: photo consent given by Maria Santos.'],
    'in person, by staff' => [true, 'Amara: photo consent recorded by Maria Santos — the parent said yes in person.'],
]);

it('clears consent on withdrawal and notes that the photo is hidden', function (): void {
    $by = User::factory()->create(['name' => 'Maria Santos']);
    $child = childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara', 'photo_path' => 'photos/a.jpg', 'photo_consent_at' => now(), 'photo_consent_by' => $by->id]);

    (new UpdateChildPhotoConsentJob(child: $child, given: false, by: $by))->handle();

    $child->refresh();
    expect($child->photo_consent_at)->toBeNull()
        ->and($child->photo_consent_by)->toBeNull()
        ->and($child->photo_path)->toBe('photos/a.jpg')
        ->and(Note::query()->sole()->body)->toBe('Amara: photo consent withdrawn by Maria Santos — the photo is hidden.');
});
