<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
 * The printable registration poster (plan §6 R1 "printable QR poster"): a QR code that opens this church's
 * registration form, for the foyer. Any volunteer can print it.
 */

it('gives a volunteer a poster whose QR code opens this church\'s registration form', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf', 'name' => 'CCF']);
    $user = User::factory()->create();
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id, 'role' => RoleEnum::Lifeshaper, 'scope_type' => ScopeTypeEnum::Organization, 'scope_id' => $org->id]);

    actingAs($user)->get('/ccf/poster')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Org/Poster')
        ->where('church.name', 'CCF')
        ->where('registerUrl', url('/ccf/register'))
        ->where('qr', fn (string $qr): bool => str_starts_with($qr, 'data:image/svg+xml;base64,')
            && str_contains((string) base64_decode(substr($qr, 26), true), '<svg')));
});

it('is not for a parent', function (): void {
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => Branch::factory()->for($org)->create()->id]);
    $parent = User::factory()->create();
    Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $parent->id]);

    actingAs($parent)->get('/ccf/poster')->assertNotFound();
});

it('draws a different code for a different address', function (): void {
    $a = (new CreateQrCodeSvgJob('https://example.test/ccf/register'))->handle();
    $b = (new CreateQrCodeSvgJob('https://example.test/other/register'))->handle();

    expect($a)->toStartWith('<?xml')->toContain('<svg')->and($a)->not->toBe($b);
});
