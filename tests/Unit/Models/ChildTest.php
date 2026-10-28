<?php

declare(strict_types=1);

use App\Data\Models\Child;

/* A Child's full name, the one way every list and Excel writes it. */

it('joins first, middle and last name, with no gap where a part is missing or padded', function (?string $middle, string $last, string $full): void {
    $child = (new Child)->forceFill(['first_name' => ' Amara ', 'middle_name' => $middle, 'last_name' => $last]);

    expect($child->fullName())->toBe($full);
})->with([
    'with a middle name' => ['Joy', 'Cruz', 'Amara Joy Cruz'],
    'no middle name' => [null, 'Cruz', 'Amara Cruz'],
    'an empty middle name' => ['', 'Cruz', 'Amara Cruz'],
    'spaces inside a part' => ['Joy  Marie', 'Dela  Cruz', 'Amara Joy Marie Dela Cruz'],
]);

it('gives a short name — first and last, no middle — for a tight row', function (?string $middle, string $short): void {
    $child = (new Child)->forceFill(['first_name' => ' Amara ', 'middle_name' => $middle, 'last_name' => 'Dela  Cruz']);

    expect($child->shortName())->toBe($short);
})->with([
    'with a middle name' => ['Joy', 'Amara Dela Cruz'],
    'without one' => [null, 'Amara Dela Cruz'],
]);
