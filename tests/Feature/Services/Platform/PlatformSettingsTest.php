<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The church's settings (spec item 12), the platform admin's: its time zone, the fee for a lost or damaged name card,
 * and the date Moving-Up measures ages on.
 */

it('shows and saves the time zone, the card fee and the Moving-Up date — a lost card then costs the new fee @story#199 @story#201', function (): void {
    $church = church(['timezone' => 'Asia/Dubai', 'moving_up_reference_date' => '2027-02-01']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $platform = User::factory()->create(['is_platform_admin' => true]);

    actingAs($platform)->get('/platform/settings')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Platform/Settings')
        ->where('settings', ['timezone' => 'Asia/Dubai', 'card_fee' => '10', 'moving_up_date' => '2027-02-01']));

    actingAs($platform)->put('/platform/settings', ['timezone' => 'Asia/Manila', 'card_fee' => '15.50', 'moving_up_date' => '2027-03-01'])
        ->assertRedirect('/platform/settings')->assertSessionHas('success', 'Settings saved.');

    $church->refresh();
    expect([$church->timezone, $church->card_fee_minor, $church->moving_up_reference_date?->toDateString()])->toBe(['Asia/Manila', 1550, '2027-03-01']);

    $amara = childOf(familyAt($dubai));
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dubai)))->post("/dubai/serve/children/{$amara->id}/card-requests", ['reason' => 'lost']);
    expect(CardRequest::query()->where('child_id', $amara->id)->sole()->fee_minor)->toBe(1550);
});

it('keeps the fee a card was asked for at when the fee changes after @story#199', function (): void {
    $church = church();
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $amara = childOf(familyAt($dubai));
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dubai)))->post("/dubai/serve/children/{$amara->id}/card-requests", ['reason' => 'lost']);

    actingAs(User::factory()->create(['is_platform_admin' => true]))
        ->put('/platform/settings', ['timezone' => 'Asia/Dubai', 'card_fee' => '0', 'moving_up_date' => '2027-02-01'])->assertSessionHasNoErrors();

    expect($church->refresh()->card_fee_minor)->toBe(0)
        ->and(CardRequest::query()->where('child_id', $amara->id)->sole()->fee_minor)->toBe(1000);
});

it('refuses a time zone that is not one, a fee below nothing, and a date that is not one @story#200', function (array $payload, string $field): void {
    church();

    actingAs(User::factory()->create(['is_platform_admin' => true]))
        ->put('/platform/settings', [...['timezone' => 'Asia/Dubai', 'card_fee' => '10', 'moving_up_date' => '2027-02-01'], ...$payload])
        ->assertSessionHasErrors($field);
})->with([
    'time zone' => [['timezone' => 'Mars/Olympus'], 'timezone'],
    'fee below nothing' => [['card_fee' => '-1'], 'card_fee'],
    'fee over 1000' => [['card_fee' => '1000.01'], 'card_fee'],
    'fee with fils of fils' => [['card_fee' => '10.555'], 'card_fee'],
    'date' => [['moving_up_date' => 'soon'], 'moving_up_date'],
]);

it('is for the platform admin only @story#191', function (): void {
    $dubai = satelliteOf(church());

    actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->get('/platform/settings')->assertNotFound();
    actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->put('/platform/settings', ['timezone' => 'UTC', 'card_fee' => '1', 'moving_up_date' => '2027-01-01'])->assertNotFound();
});
