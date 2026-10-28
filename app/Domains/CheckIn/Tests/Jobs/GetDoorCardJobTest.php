<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Domains\CheckIn\DataObjects\DoorCardData;
use App\Domains\CheckIn\Jobs\GetDoorCardJob;
use App\Enums\CardMatchEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * A name card at the door (slice 3): which children the card's code names, in this church, and whether one is ticked.
 * One exact match ticks that child; several exact matches, or a close match (a middle name on ONE side only), show the
 * families and tick nobody; no match says so. Never two different middle names, never fuzzier.
 */

function readCard(Church $church, string $query, bool $card = false): ?DoorCardData
{
    return (new GetDoorCardJob(church: $church, query: $query, card: $card))->handle();
}

it('ticks the one child whose full name the card spells, and shows their family', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $amara = childOf($family, ['first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Reyes']);
    childOf($family, ['first_name' => 'Ben', 'middle_name' => null, 'last_name' => 'Reyes']);

    $read = readCard($church, 'amarajoyreyes');

    expect([$read?->scan->code, $read?->scan->match, $read?->scan->child_ids, $read?->scan->note])->toBe(['AMARAJOYREYES', CardMatchEnum::Exact, [$amara->id], null])
        ->and($read?->families->pluck('id')->all())->toBe([$family->id])
        ->and($read?->searchToo)->toBeFalse();
});

it('ticks a short name too, when it matches exactly', function (): void {
    $church = church();
    $bong = childOf(familyAt(satelliteOf($church)), ['first_name' => 'Bo', 'middle_name' => null, 'last_name' => 'Ng']);

    expect(readCard($church, 'BONG')?->scan->child_ids)->toBe([$bong->id]);
});

it('shows both families when two children share the key, and ticks nobody', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    childOf(familyAt($satellite), ['first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Reyes']);
    childOf(familyAt($satellite), ['first_name' => 'Amara Joy', 'middle_name' => null, 'last_name' => 'Reyes']);

    $read = readCard($church, 'AMARAJOYREYES');

    expect([$read?->scan->match, $read?->scan->child_ids, $read?->families->count()])->toBe([CardMatchEnum::Several, [], 2]);
});

it('matches closely only when ONE side has no middle name — shown, never ticked', function (string $code, ?string $found): void {
    $church = church();
    $satellite = satelliteOf($church);
    childOf(familyAt($satellite), ['first_name' => 'Adam', 'middle_name' => null, 'last_name' => 'Abergos']);
    childOf(familyAt($satellite), ['first_name' => 'Bea', 'middle_name' => 'Marie', 'last_name' => 'Santos']);
    childOf(familyAt($satellite), ['first_name' => 'Ana', 'middle_name' => 'Maria', 'last_name' => 'Reyes']);

    $read = readCard($church, $code);

    expect($found === null ? [$read?->scan->match, $read?->families->count()] : [$read?->scan->match, $read?->scan->child_ids, $read?->families->first()?->children->first()?->fullName()])
        ->toBe($found === null ? [CardMatchEnum::None, 0] : [CardMatchEnum::Close, [], $found]);
})->with([
    'the card has a middle name the child lacks' => ['ADAMMATTHIASABERGOS', 'Adam Abergos'],
    'the child has a middle name the card lacks' => ['BEASANTOS', 'Bea Marie Santos'],
    'two different middle names: a cousin, never' => ['ANAJOYREYES', null],
    'a different last name' => ['ADAMMATTHIASABERGO', null],
    'a different first name' => ['ADEMMATTHIASABERGOS', null],
]);

it('prefers the exact child to a close one; a close match is only a child with no middle name on one side', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $plain = childOf(familyAt($satellite), ['first_name' => 'Adam', 'middle_name' => null, 'last_name' => 'Abergos']);
    $lee = childOf(familyAt($satellite), ['first_name' => 'Adam', 'middle_name' => 'Lee', 'last_name' => 'Abergos']);

    $exact = readCard($church, 'ADAMLEEABERGOS');
    $matthias = readCard($church, 'ADAMMATTHIASABERGOS');

    expect([$exact?->scan->match, $exact?->scan->child_ids])->toBe([CardMatchEnum::Exact, [$lee->id]])
        // Adam Matthias Abergos's card: the child with no middle name is close; the one called Lee is not.
        ->and([$matthias?->scan->match, $matthias?->scan->child_ids, $matthias?->families->pluck('id')->all()])
        ->toBe([CardMatchEnum::Close, [], [$plain->family_id]]);
});

it('finds nothing in another church, nor for a child whose name has no letters', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    childOf(familyAt(satelliteOf(church())), ['first_name' => 'Zed', 'middle_name' => null, 'last_name' => 'Elsewhere']);
    childOf(familyAt($satellite), ['first_name' => '123', 'middle_name' => null, 'last_name' => 'Cruz']);

    expect(readCard($church, 'ZEDELSEWHERE')?->scan->match)->toBe(CardMatchEnum::None)
        ->and(readCard($church, 'XXXXCRUZ')?->scan->match)->toBe(CardMatchEnum::None)
        ->and(readCard($church, 'CRUZ')?->scan->match)->toBe(CardMatchEnum::None);
});

it('reads only a word of letters as a card; anything else is an ordinary search — and a typed card with no match searches too', function (): void {
    $church = church();

    expect(readCard($church, 'Ana Reyes'))->toBeNull()
        ->and(readCard($church, '0501234567'))->toBeNull()
        ->and(readCard($church, 'A'))->toBeNull()
        ->and(readCard($church, '', card: true))->toBeNull()
        ->and(readCard($church, '1234', card: true))->toBeNull()
        ->and(readCard($church, 'REYES')?->searchToo)->toBeTrue()
        ->and(readCard($church, 'NOBODYHERE', card: true)?->searchToo)->toBeFalse();
});
