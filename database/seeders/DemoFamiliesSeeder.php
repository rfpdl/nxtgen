<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Operations\Registration\RegisterFamilyOperation;
use Illuminate\Database\Seeder;

/**
 * Demo families to click around with on a developer's machine (Roy 2026-10-27). Runs after DemoChurchSeeder from
 * DatabaseSeeder outside production; NEVER in production and never in the browser tests (E2ESeeder has its own
 * families). Families go through RegisterFamilyOperation without emails, so every row is one the app itself makes.
 *
 * Every case the door and the lists show: each Level and Squad, an allergy, an inactive child, a Graduated one, one
 * with no Level, a visiting House Church family, a second guardian (no login). Ages are worked out from today, so
 * the suggested Levels stay right whenever it runs. Plus "Any day (demo)": a class with no set day, so the door has
 * something to check into on any day of the week.
 */
final class DemoFamiliesSeeder extends Seeder
{
    public function run(): void
    {
        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        $dxb = Satellite::query()->where('church_id', $ccf->id)->where('slug', 'ccf-dxb')->firstOrFail();
        $house = Satellite::query()->where('church_id', $ccf->id)->where('slug', 'house-church')->firstOrFail();

        Schedule::query()->firstOrCreate(
            ['church_id' => $ccf->id, 'satellite_id' => $dxb->id, 'name' => 'Any day (demo)', 'is_active' => true],
            ['weekday' => null, 'starts_at' => null, 'ends_at' => null],
        );

        if (Family::query()->where('church_id', $ccf->id)->exists()) {
            return;   // already seeded: never a second copy of every family
        }

        $this->family($ccf, $dxb, ['Ana', 'Reyes', RelationshipEnum::Mother, '+971501110001'], [
            ['Amara', 'Girl', 7, 'Peanuts'], ['Ben', 'Boy', 5],
        ], other: ['Paolo', 'Reyes', RelationshipEnum::Father, '+971501110002']);
        $this->family($ccf, $dxb, ['Grace', 'Santos', RelationshipEnum::Mother, '+971501110003'], [
            ['Lucas', 'Boy', 9], ['Sofia', 'Girl', 11, 'Dairy, eggs'], ['Mia', 'Girl', 4],
        ]);
        $this->family($ccf, $dxb, ['Mark', 'Dela Cruz', RelationshipEnum::Father, '+971501110004'], [
            ['Miguel', 'Boy', 8], ['Noah', 'Boy', 10],
        ]);
        $this->family($ccf, $dxb, ['Joy', 'Bautista', RelationshipEnum::Mother, '+971501110005'], [
            ['Isabel', 'Girl', 6], ['Gabe', 'Boy', 13],
        ], other: ['Lita', 'Bautista', RelationshipEnum::Grandparent, '+971501110006']);
        $this->family($ccf, $dxb, ['Carlo', 'Mendoza', RelationshipEnum::Father, '+971501110007'], [
            ['Ethan', 'Boy', 12], ['Chloe', 'Girl', 9],
        ]);
        $this->family($ccf, $dxb, ['Rose', 'Garcia', RelationshipEnum::Mother, '+971501110008'], [
            ['Lola', 'Girl', 3],
        ]);
        $this->family($ccf, $house, ['Bea', 'Villa', RelationshipEnum::Mother, '+971501110009'], [
            ['Pia', 'Girl', 7], ['Jun', 'Boy', 10, 'Shellfish'],
        ]);
        $this->family($ccf, $house, ['Tess', 'Ramos', RelationshipEnum::Mother, '+971501110010'], [
            ['Kai', 'Boy', 5],
        ]);

        // As the door will find them: Noah stopped coming, Gabe moved up out of Level 4, Lola has no Level yet
        // (3 is under the chart, so registration suggested none).
        $child = fn (string $first, string $last) => Child::query()->where('church_id', $ccf->id)->where('first_name', $first)->where('last_name', $last);
        $child('Noah', 'Dela Cruz')->update(['status' => ChildStatusEnum::Inactive]);
        $child('Gabe', 'Bautista')->update(['status' => ChildStatusEnum::Graduated, 'level' => 4]);
    }

    /**
     * @param  array{0: string, 1: string, 2: RelationshipEnum, 3: string}  $primary  first, last, relationship, phone
     * @param  list<array{0: string, 1: string, 2: int, 3?: string}>  $children  name, Boy|Girl, age, allergies
     * @param  array{0: string, 1: string, 2: RelationshipEnum, 3: string}|null  $other  a second guardian, no login
     */
    private function family(Church $church, Satellite $satellite, array $primary, array $children, ?array $other = null): void
    {
        [$first, $last, $relationship, $phone] = $primary;
        $email = mb_strtolower(str_replace(' ', '', $last)).'@nxtgen.test';

        (new RegisterFamilyOperation(new FamilyRegistrationData(
            church: $church,
            homeSatellite: $satellite,
            primary: new GuardianDetailsData(firstName: $first, lastName: $last, relationship: $relationship, phone: $phone, email: $email),
            children: array_map(fn (array $c): ChildDetailsData => new ChildDetailsData(
                firstName: $c[0],
                middleName: null,
                lastName: $last,
                nickname: $c[0],
                gender: $c[1] === 'Boy' ? GenderEnum::Boy : GenderEnum::Girl,
                dateOfBirth: now()->subYears($c[2])->subMonths(2)->toDateString(),
                allergies: $c[3] ?? null,
            ), $children),
            areaText: 'Al Barsha',
            privacyConsented: true,
            source: RegistrationSourceEnum::Import,
            notify: false,
            otherGuardians: $other === null ? [] : [new GuardianEditData(first_name: $other[0], last_name: $other[1], relationship: $other[2], phone: $other[3], ministries: [])],
        )))->handle();
    }
}
