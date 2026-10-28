<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Schedule;
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

    $families = Family::query()->with(['primaryGuardian.user', 'homeSatellite', 'children'])->get()
        ->reject(fn (Family $f): bool => in_array($f->primaryGuardian?->first_name, E2ESeeder::DOOR_GUARDIANS, true));
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

    // The classes journey finds CCF DXB's Saturday classes, and House Church with none.
    $classes = Schedule::query()->with('satellite')->where('is_active', true)->get();
    expect($classes->map(fn (Schedule $s): string => $s->satellite->name)->unique()->values()->all())->toBe(['CCF DXB']);
    foreach ($classes as $class) {
        expect($world)->toContain("name: '{$class->name}', when: '{$class->weekday?->everyLabel()} · {$class->startsAt()}–{$class->endsAt()}'");
    }

    // The door journeys: each browser's own Cruz family at CCF DXB and visiting Villa family at House Church.
    foreach (E2ESeeder::DOOR_GUARDIANS as $project => $first) {
        expect($world)->toContain("'{$project}': '{$first}'");
        $cruz = Family::query()->with(['children', 'homeSatellite'])->whereHas('primaryGuardian', fn ($g) => $g->where('first_name', $first)->where('last_name', 'Cruz'))->sole();
        $kid = fn (string $nickname): Child => $cruz->children->firstOrFail(fn (Child $c): bool => $c->nickname === $nickname);
        expect($cruz->homeSatellite->name)->toBe('CCF DXB')
            ->and([$kid('Amara')->allergies, $kid('Noah')->status->value, $kid('Lola')->level, $kid('Gabe')->status->value, $kid('Ben')->level])
            ->toBe(['Peanuts', 'inactive', null, 'graduated', 1]);
        $villa = Family::query()->with(['children', 'homeSatellite'])->whereHas('primaryGuardian', fn ($g) => $g->where('first_name', $first)->where('last_name', 'Villa'))->sole();
        expect([$villa->homeSatellite->name, $villa->children->sole()->nickname])->toBe(['House Church', 'Pia']);

        // Who's in: the browser's own Reyes girls, both in Level 2 Girls at CCF DXB.
        $girls = E2ESeeder::ROLL_CALL_GIRLS[$project];
        expect($world)->toContain("'{$project}': ['".implode("', '", $girls)."']");
        $reyes = Family::query()->with(['children', 'homeSatellite'])->whereHas('primaryGuardian', fn ($g) => $g->where('first_name', $first)->where('last_name', 'Reyes'))->sole();
        expect([$reyes->homeSatellite->name, $reyes->children->map(fn (Child $c): array => [$c->nickname, $c->level, $c->gender->value])->all()])
            ->toBe(['CCF DXB', [...array_map(fn (string $girl): array => [$girl, 2, 'girl'], $girls), ['Amara', 2, 'girl']]]);

        // The scan journey: this browser's Reyes Amara, whose name card it types — her key is the browser's own.
        $middle = E2ESeeder::SCAN_MIDDLE_NAMES[$project];
        $key = 'AMARA'.mb_strtoupper($middle).'REYES';
        expect($world)->toContain("'{$project}': '{$key}'")
            ->and(Child::query()->where('name_key', $key)->sole()->family_id)->toBe($reyes->id);
    }
    foreach (["allergic: 'Amara'", "active: 'Ben'", "inactive: 'Noah'", "noLevel: 'Lola'", "graduated: 'Gabe'", "allergy: 'Peanuts'", "suggestedLevel: 'Level 1'"] as $line) {
        expect($world)->toContain($line);
    }
    expect($world)->toContain("visitor: 'Pia'")
        ->and(DuplicateCandidate::query()->count())->toBe(0);

    expect(User::query()->where('email', E2ESeeder::HOUSE_ADMIN_EMAIL)->firstOrFail()->roleAssignments()->first()?->role)->toBe(RoleEnum::Admin);
});
