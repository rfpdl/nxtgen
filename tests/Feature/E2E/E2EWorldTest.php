<?php

declare(strict_types=1);

use App\Data\Models\Family;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Database\Seeders\E2ESeeder;

use function Pest\Laravel\seed;

/*
 * `tests/e2e/support/world.ts` names what E2ESeeder creates. The browser suite runs outside `make check`, so
 * drift there would only show as a confusing failure at a login form (palo, twice). This keeps them in step.
 */

it('seeds exactly what the browser journeys expect', function (): void {
    seed(E2ESeeder::class);
    $world = (string) file_get_contents(base_path('tests/e2e/support/world.ts'));

    foreach (['admin@nxtgen.test', 'lifeshaper@nxtgen.test', E2ESeeder::HOUSE_ADMIN_EMAIL] as $email) {
        expect($world)->toContain("'{$email}'");
        expect(User::query()->where('email', $email)->firstOrFail()->roleAssignments()->where('is_active', true)->exists())->toBeTrue();
    }

    $families = Family::query()->with(['primaryGuardian.user', 'homeSatellite', 'children'])->get();
    expect($families)->toHaveCount(2);

    foreach ($families as $family) {
        expect($world)->toContain("guardian: '{$family->contactName()}'")
            ->and($world)->toContain("email: '{$family->contactEmail()}'")
            ->and($world)->toContain("satellite: '{$family->homeSatellite->name}'")
            ->and($world)->toContain("child: '{$family->children->first()?->nickname}'");
    }

    // The duplicates journey re-registers with this family's phone.
    $dxb = $families->first(fn (Family $f): bool => $f->contactEmail() === 'delacruz@nxtgen.test');
    expect($world)->toContain("phone: '0501000001'")
        ->and(substr((string) preg_replace('/\D+/', '', (string) $dxb?->primaryGuardian?->phone), -9))->toBe('501000001');

    expect(User::query()->where('email', E2ESeeder::HOUSE_ADMIN_EMAIL)->firstOrFail()->roleAssignments()->first()?->role)->toBe(RoleEnum::Admin);
});
