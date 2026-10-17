<?php

declare(strict_types=1);

namespace Tests\Feature\ResponseAllowlist;

use Illuminate\Support\Facades\Storage;
use Tests\Support\AccessWorld;
use Tests\Support\Actor;

use function Pest\Laravel\actingAs;
use function Tests\Support\exportedRows;

/*
 * Response allowlists (plan §3.3, TODO #18, SECURITY #5): for every page that carries people data, the EXACT props
 * each kind of viewer receives, as key paths (`family.children.*.allergies`; a list is `*`, an empty list or a null
 * is the bare key). A field that appears on a page but not on its list fails here — so adding data to a page is a
 * deliberate act, made by editing the list below. Enum option lists are collapsed to their name (`enums.gender`):
 * they are labels, not people.
 *
 * The world is AccessWorld: the family at Home (parent Janice, child Amara with a photo, allergies and an illness),
 * another family at Home, a parent-and-Lifeshaper whose family is Away, a possible duplicate, a staff note.
 */

/** What HandleInertiaRequests shares with every page under a church, apart from the staff menu. */
const SHARED = ['auth.can_switch', 'auth.user.id', 'auth.user.name', 'church.name', 'church.slug', 'errors', 'flash.error', 'flash.success'];

/** A page that is not a staff page: the staff menu is null. */
const FRAME = [...SHARED, 'church_nav'];

/** A staff page: the staff menu's two switches. */
const STAFF_FRAME = [...SHARED, 'church_nav.can_manage_people', 'church_nav.can_review_duplicates'];

/** Health, photo and contact fields: never on a list or search page, whoever asks — only the allergy yes/no. */
const NEVER_ON_LISTS = '/photo|allerg|illness|fear|dislike|guardian_notes|phone|notes|ministries/';

/**
 * The key paths a list or search page must never carry.
 *
 * @param  array<string, mixed>  $props
 * @return list<string>
 */
function forbiddenOnLists(array $props): array
{
    return array_values(array_filter(
        keyPaths($props),
        fn (string $p): bool => preg_match(NEVER_ON_LISTS, $p) === 1 && ! str_ends_with($p, '.has_allergies'),
    ));
}

/** A family's lite view — the door search and the duplicates page (FamilySearchResultData). */
const LITE_FAMILY = ['can_open', 'can_resend', 'children.*', 'email_confirmed', 'id', 'masked_email', 'primary_name', 'satellite'];

/** A family opened by staff, minus what only an editor gets (Church/Families/Show). */
const FAMILY_VIEW = [
    'can_set_level', 'enums.child_status', 'enums.gender', 'enums.relationship',
    'family.area', 'family.can_edit', 'family.email', 'family.email_confirmed', 'family.id', 'family.primary_name',
    'family.registered_by', 'family.satellite',
    'family.children.*.allergies', 'family.children.*.date_of_birth', 'family.children.*.dislikes', 'family.children.*.fears',
    'family.children.*.first_name', 'family.children.*.gender', 'family.children.*.guardian_notes', 'family.children.*.id',
    'family.children.*.illnesses', 'family.children.*.last_name', 'family.children.*.level', 'family.children.*.middle_name',
    'family.children.*.nickname', 'family.children.*.photo_consent', 'family.children.*.photo_url', 'family.children.*.status_label',
    'family.guardians.*.first_name', 'family.guardians.*.last_name', 'family.guardians.*.phone', 'family.guardians.*.relationship_label',
    'notes.*.at', 'notes.*.author', 'notes.*.body', 'notes.*.id',
];

/** What an editor of the family gets on top: the edit forms' current values. */
const FAMILY_EDIT_DETAILS = [
    'details.area_text', 'details.dgroup_area_text', 'details.dgroup_leader_name', 'details.dgroup_leader_phone', 'details.fb_account',
    'details.home_satellite_id', 'details.in_dgroup', 'details.lifestage', 'details.notes', 'details.wants_dgroup', 'details.wants_fb_group',
    'primary.first_name', 'primary.last_name', 'primary.ministries', 'primary.phone', 'primary.relationship',
    'satellites.*.id', 'satellites.*.name',
];

/** A list page's filters, options and pagination (families and children lists). */
const LIST_FRAME = [
    'enums.child_status', 'filters.duplicates', 'filters.level', 'filters.q', 'filters.satellite', 'filters.status', 'filters.unconfirmed',
    'options.levels.*.level', 'options.levels.*.name', 'options.satellites.*.id', 'options.satellites.*.name',
    'pagination.last_page', 'pagination.page', 'pagination.per_page', 'pagination.total',
];

/**
 * Every key path in a page's props: nested keys joined by dots, a list's items as `*`, an empty list or a null as
 * the bare key, and enum options as the enum's name.
 *
 * @return list<string>
 */
function keyPaths(mixed $value, string $prefix = ''): array
{
    if (! is_array($value) || $value === [] || preg_match('/^enums\.\w+$/', $prefix) === 1) {
        return [$prefix];
    }

    $paths = [];
    foreach ($value as $key => $item) {
        $path = ($prefix === '' ? '' : "{$prefix}.").(array_is_list($value) ? '*' : (string) $key);
        array_push($paths, ...keyPaths($item, $path));
    }
    $paths = array_values(array_unique($paths));
    sort($paths);

    return $paths;
}

/**
 * The props an actor's page carries, in a fresh world.
 *
 * @param  callable(AccessWorld): string  $url
 * @return array<string, mixed>
 */
function propsOf(Actor $actor, callable $url): array
{
    Storage::fake('photos');
    $world = AccessWorld::make();
    $user = $world->user($actor);
    if ($user === null) {
        throw new \LogicException('An allowlist is for a signed-in actor.');
    }

    $page = actingAs($user)->get($url($world))->assertOk()->viewData('page');

    /** @var array{props: array<string, mixed>} $page */
    return $page['props'];
}

/**
 * @param  array<array-key, string>  ...$parts
 * @return list<string>
 */
function allowlist(array ...$parts): array
{
    $all = array_values(array_unique(array_merge(...array_map(array_values(...), $parts))));
    sort($all);

    return $all;
}

/**
 * @param  list<string>  $paths
 * @return list<string>
 */
function prefixed(string $prefix, array $paths): array
{
    return array_map(fn (string $p): string => "{$prefix}.{$p}", $paths);
}

/** @return array<string, array{0: Actor}> */
function actors(Actor ...$actors): array
{
    $named = [];
    foreach ($actors as $actor) {
        $named[$actor->value] = [$actor];
    }

    return $named;
}

/**
 * One prop's list, by a dotted key.
 *
 * @param  array<array-key, mixed>  $props
 * @return list<mixed>
 */
function listAt(array $props, string $key): array
{
    $value = data_get($props, $key);

    return is_array($value) ? array_values($value) : [];
}

describe('the door search — the church-wide lite view', function (): void {
    it('shows any staff only names, satellite, a masked email and nicknames — never a photo or a health field @story#46', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/families/new?q=Amara');

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, ['enums.gender', 'enums.relationship', 'query', 'satellites.*.id', 'satellites.*.name'], prefixed('results.*', LITE_FAMILY)))
            ->and(forbiddenOnLists($props))->toBe([])
            ->and(data_get($props, 'results.0.masked_email'))->toBe('ja•••@example.com');
    })->with(actors(...Actor::staff()));
});

describe('a family opened by staff', function (): void {
    it('gives an admin who may edit it the edit details, and the Level list only to a church admin @story#50 @story#52', function (Actor $actor, bool $setsLevels): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => "/ccf/families/{$w->family->id}");

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, FAMILY_VIEW, FAMILY_EDIT_DETAILS, $setsLevels ? ['levels.*.level', 'levels.*.name'] : ['levels']))
            ->and(data_get($props, 'family.email'))->toBe('janice@example.com');
    })->with([
        Actor::ChurchAdmin->value => [Actor::ChurchAdmin, true],
        Actor::HomeAdmin->value => [Actor::HomeAdmin, false],
    ]);

    it('gives a Lifeshaper the family and its children, but no edit details and a masked email @story#50 @story#53', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => "/ccf/families/{$w->family->id}");

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, FAMILY_VIEW, ['details', 'primary', 'satellites', 'levels']))
            ->and([data_get($props, 'details'), data_get($props, 'primary'), data_get($props, 'satellites')])->toBe([null, null, []])
            ->and(data_get($props, 'family.email'))->toBe('ja•••@example.com');
    })->with(actors(Actor::ChurchLifeshaper, Actor::HomeLifeshaper, Actor::BothHats, Actor::HomeParentLifeshaper));
});

describe('the registration lists', function (): void {
    it('lists families with nicknames only, the email in full only for an editor @story#43', function (Actor $actor, string $email): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/families');
        $janice = collect(listAt($props, 'families'))->first(fn (mixed $f): bool => in_array('Amara', listAt(is_array($f) ? $f : [], 'children'), true));

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, LIST_FRAME, prefixed('families.*', ['can_resend', 'children.*', 'email', 'email_confirmed', 'id', 'primary_name', 'registered_by', 'registered_on', 'satellite'])))
            ->and(forbiddenOnLists($props))->toBe([])
            ->and(data_get($janice, 'email'))->toBe($email);
    })->with([
        Actor::ChurchAdmin->value => [Actor::ChurchAdmin, 'janice@example.com'],
        Actor::HomeAdmin->value => [Actor::HomeAdmin, 'janice@example.com'],
        Actor::ChurchLifeshaper->value => [Actor::ChurchLifeshaper, 'ja•••@example.com'],
        Actor::HomeLifeshaper->value => [Actor::HomeLifeshaper, 'ja•••@example.com'],
    ]);

    it('never lists a family outside an away staff member\'s satellite @story#44', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/families');

        expect(collect(listAt($props, 'families'))->pluck('satellite')->unique()->values()->all())->toBe(['Away']);
    })->with(actors(Actor::AwayAdmin, Actor::AwayLifeshaper));

    it('never lists a child outside an away staff member\'s satellite @story#58', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/children');

        expect(collect(listAt($props, 'children'))->pluck('satellite')->unique()->values()->all())->toBe(['Away']);
    })->with(actors(Actor::AwayAdmin, Actor::AwayLifeshaper));

    it('downloads only the away satellite\'s families for away staff @story#45', function (Actor $actor): void {
        Storage::fake('photos');
        $world = AccessWorld::make();

        $rows = exportedRows(actingAs($world->user($actor) ?? throw new \LogicException('Away staff sign in.'))->get('/ccf/families/export'));
        $satellites = array_unique(array_map(fn (string $row): string => explode('|', $row)[3] ?? '', array_slice($rows, 1)));

        expect(array_values($satellites))->toBe(['Away']);
    })->with(actors(Actor::AwayAdmin, Actor::AwayLifeshaper));

    it('downloads exactly the allowlisted columns — no health field but allergies, no phone, no photo @story#45', function (): void {
        Storage::fake('photos');
        $world = AccessWorld::make();

        $rows = exportedRows(actingAs($world->user(Actor::ChurchAdmin) ?? throw new \LogicException('An admin signs in.'))->get('/ccf/families/export'));

        expect($rows[0])->toBe('Family|Email|Email confirmed|Satellite|Child nickname|Child full name|Birthday|Level|Allergies|Status|Registered by|Registered on');
    });

    it('lists children with an allergy yes/no only — no health details, no photo @story#58', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/children');

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, LIST_FRAME, prefixed('children.*', ['age', 'can_resend', 'date_of_birth', 'family_id', 'full_name', 'has_allergies', 'id', 'level', 'nickname', 'satellite', 'status_label'])))
            ->and(forbiddenOnLists($props))->toBe([]);
    })->with(actors(...Actor::staff()));
});

describe('admin pages', function (): void {
    it('shows possible duplicates as two lite families, and none outside the admin\'s satellite @story#70', function (Actor $actor, bool $seesPair): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/duplicates');
        $pair = ['duplicates.*.id', 'duplicates.*.reason_label', ...prefixed('duplicates.*.subject', LITE_FAMILY), ...prefixed('duplicates.*.candidate', LITE_FAMILY)];

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, $seesPair ? $pair : ['duplicates']));
    })->with([
        Actor::ChurchAdmin->value => [Actor::ChurchAdmin, true],
        Actor::HomeAdmin->value => [Actor::HomeAdmin, true],
        Actor::AwayAdmin->value => [Actor::AwayAdmin, false],
    ]);

    it('shows People as names, emails and roles — and an away admin only their own satellite\'s @story#74', function (Actor $actor, bool $seesHome): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/people');
        $labels = collect(listAt($props, 'people'))->flatMap(fn (mixed $p): array => listAt(is_array($p) ? $p : [], 'roles'))->pluck('label');

        expect(keyPaths($props))->toBe(allowlist(STAFF_FRAME, ['grant_options.*.label', 'grant_options.*.role', 'grant_options.*.scope_id', 'grant_options.*.scope_type'], prefixed('people.*', ['email', 'id', 'name', 'roles.*.can_remove', 'roles.*.id', 'roles.*.label'])))
            ->and($labels->contains(fn (mixed $l): bool => is_string($l) && str_contains($l, 'Home')))->toBe($seesHome);
    })->with([
        Actor::ChurchAdmin->value => [Actor::ChurchAdmin, true],
        Actor::HomeAdmin->value => [Actor::HomeAdmin, true],
        Actor::AwayAdmin->value => [Actor::AwayAdmin, false],
    ]);
});

describe('the parent\'s own family page', function (): void {
    it('gives a parent their own family, its children and the edit forms — no staff notes, no registration source @story#28', function (Actor $actor): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/family');

        expect(keyPaths($props))->toBe(allowlist(FRAME, ['enums.gender', 'enums.relationship', 'family.area', 'family.home_satellite', 'satellites.*.id', 'satellites.*.name'],
            prefixed('family.children.*', ['allergies', 'date_of_birth', 'dislikes', 'fears', 'first_name', 'gender', 'guardian_notes', 'id', 'illnesses', 'last_name', 'level', 'middle_name', 'nickname', 'photo_consent', 'photo_url', 'status_label']),
            prefixed('family.guardians.*', ['first_name', 'last_name', 'phone', 'relationship_label']),
            array_values(array_filter(FAMILY_EDIT_DETAILS, fn (string $p): bool => str_starts_with($p, 'details.'))),
            prefixed('me', ['first_name', 'last_name', 'ministries', 'phone', 'relationship'])));
    })->with(actors(...Actor::parents()));

    it('shows each parent only their own family @story#29', function (Actor $actor, string $guardian, string $child): void {
        $props = propsOf($actor, fn (AccessWorld $w): string => '/ccf/family');

        expect(collect(listAt($props, 'family.guardians'))->pluck('first_name')->all())->toBe([$guardian])
            ->and(collect(listAt($props, 'family.children'))->pluck('nickname')->all())->toBe([$child]);
    })->with([
        Actor::Parent->value => [Actor::Parent, 'Janice', 'Amara'],
        Actor::OtherParent->value => [Actor::OtherParent, 'Carla', 'Bea'],
        Actor::BothHats->value => [Actor::BothHats, 'Rhea', 'Cody'],
        Actor::HomeParentLifeshaper->value => [Actor::HomeParentLifeshaper, 'Hana', 'Hugo'],
        Actor::RevokedParent->value => [Actor::RevokedParent, 'Rosa', 'Rio'],
    ]);
});
