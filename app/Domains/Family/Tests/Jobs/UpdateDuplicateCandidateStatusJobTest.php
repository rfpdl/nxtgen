<?php

declare(strict_types=1);

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\UpdateDuplicateCandidateStatusJob;
use App\Enums\DuplicateStatusEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* A reviewer's answer to a possible duplicate — who and when — noted on the NEW family's history. */

function samePhoneFamily(Satellite $satellite, string $name): Family
{
    $family = familyAt($satellite);
    guardianOf($family, User::factory()->create(), ['first_name' => $name, 'last_name' => 'Santos', 'phone' => '+971 50 123 4567']);

    return $family;
}

it('records the answer and who gave it, and notes it on the new family', function (DuplicateStatusEnum $status, string $answer): void {
    $satellite = satelliteOf(church());
    samePhoneFamily($satellite, 'Maria');
    $new = samePhoneFamily($satellite, 'Mary');
    (new CreateDuplicateCandidatesJob(family: $new))->handle();
    $pair = DuplicateCandidate::query()->sole();
    $admin = User::factory()->create();

    (new UpdateDuplicateCandidateStatusJob(pair: $pair, status: $status, by: $admin))->handle();

    $pair->refresh();
    $note = Note::query()->sole();
    expect([$pair->status, $pair->resolved_by])->toBe([$status, $admin->id])
        ->and($pair->resolved_at)->not->toBeNull()
        ->and([$note->noteable_type, $note->noteable_id, $note->author_id])->toBe(['family', $new->id, $admin->id])
        ->and($note->body)->toBe("Possible duplicate of Maria Santos (same phone): {$answer}.");
})->with([
    'not the same' => [DuplicateStatusEnum::Dismissed, 'not the same family'],
    'the same' => [DuplicateStatusEnum::Confirmed, 'the same family, flagged for merging'],
]);
