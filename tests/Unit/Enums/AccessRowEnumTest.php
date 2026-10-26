<?php

declare(strict_types=1);

use App\Enums\AccessRowEnum;

/* The access-matrix rows Release 1 serves (plan §3.2). Each staff or parent Feature names one with #[AccessRow]. */

it('keeps the stored row names the Features and tests refer to', function (): void {
    expect(array_column(AccessRowEnum::cases(), 'value'))->toBe([
        'staff_area', 'door_search', 'register_family', 'add_child', 'family_list', 'view_family', 'edit_family',
        'resend_link', 'correct_contact', 'set_child_status', 'set_child_level', 'review_duplicates', 'grant_roles',
        'own_family', 'child_photo', 'see_child_photo', 'manage_classes', 'check_in', 'attendance',
    ]);
});

it('states each rule as who may, in its own words', function (): void {
    $labels = array_map(fn (AccessRowEnum $row): string => $row->label(), AccessRowEnum::cases());

    expect(array_unique($labels))->toHaveCount(count(AccessRowEnum::cases()))
        ->and(AccessRowEnum::SetChildLevel->label())->toContain('church admin only')
        ->and(AccessRowEnum::OwnFamily->label())->toContain('not status/Level')
        ->and(AccessRowEnum::CheckIn->label())->toContain('any child of the church')
        ->and(AccessRowEnum::Attendance->label())->toContain('satellite admin at their own');
});

it('is shown as plain text, never as a badge', function (AccessRowEnum $row): void {
    expect($row->color())->toBe('muted');
})->with(AccessRowEnum::cases());
