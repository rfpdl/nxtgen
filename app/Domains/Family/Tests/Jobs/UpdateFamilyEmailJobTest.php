<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateFamilyEmailJob;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Staff correcting a family's mistyped login email: every old link dies, and the family's history says so. */

it('changes the login email, unconfirms it, ends old links, and notes the correction on the family', function (): void {
    $family = familyAt(satelliteOf(church()));
    $login = User::factory()->create(['email' => 'janice@typo.com', 'email_verified_at' => now(), 'remember_token' => 'remembered']);
    $version = $login->credential_version;
    $staff = User::factory()->create();

    $updated = (new UpdateFamilyEmailJob(family: $family, login: $login, email: ' Janice@Real.com ', by: $staff))->handle();

    $login->refresh();
    $note = Note::query()->sole();
    expect($updated->email)->toBe('janice@real.com')
        ->and($login->email_verified_at)->toBeNull()
        ->and($login->credential_version)->toBe($version + 1)
        ->and($login->remember_token)->toBeNull()
        ->and([$note->noteable_type, $note->noteable_id, $note->author_id])->toBe(['family', $family->id, $staff->id])
        ->and($note->body)->toBe('Email corrected from janice@typo.com to janice@real.com.');
});
