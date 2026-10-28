<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Operations\Registration\RegisterFamilyOperation;
use Illuminate\Database\Seeder;

/**
 * The browser tests' world (`make e2e`): the demo church plus a House Church admin and one family in each
 * satellite. Families go through RegisterFamilyOperation — the import path, no emails — so the rows are ones
 * the app itself can produce. Must match `tests/e2e/support/world.ts`.
 *
 * The door journeys run on three browsers against one database, so each browser has its OWN families to check in
 * (DOOR_GUARDIANS): a Cruz family at CCF DXB — Amara (allergic), Ben, Noah (inactive), Lola (no Level), Gabe
 * (Graduated) — and a visiting Villa family at House Church (Pia). Birthdays differ per browser, so no two are
 * "possible duplicates". For Who's in (roll call), a Reyes family at CCF DXB with two Level 2 girls whose names are
 * the browser's own (ROLL_CALL_GIRLS): the browsers share one 1PM Session and its Squads, so each ticks its own.
 */
final class E2ESeeder extends Seeder
{
    public const string HOUSE_ADMIN_EMAIL = 'house-admin@nxtgen.test';

    /** Browser project => the first name of its door families' parent. */
    public const array DOOR_GUARDIANS = ['mobile-webkit' => 'Ana', 'mobile-chromium' => 'Bela', 'desktop-chromium' => 'Carla'];

    /** Browser project => its Reyes family's two Level 2 girls, named apart so no browser ticks another's. */
    public const array ROLL_CALL_GIRLS = ['mobile-webkit' => ['Sofia', 'Isabel'], 'mobile-chromium' => ['Mia', 'Elena'], 'desktop-chromium' => ['Ruth', 'Hope']];

    public function run(): void
    {
        $this->call(DemoChurchSeeder::class);

        $ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
        $dxb = Satellite::query()->where('slug', 'ccf-dxb')->firstOrFail();
        $house = Satellite::query()->where('slug', 'house-church')->firstOrFail();

        $houseAdmin = User::query()->create(['name' => 'House Admin', 'email' => self::HOUSE_ADMIN_EMAIL, 'email_verified_at' => now()]);
        RoleAssignment::query()->create([
            'church_id' => $ccf->id, 'user_id' => $houseAdmin->id, 'role' => RoleEnum::Admin,
            'scope_type' => ScopeTypeEnum::Satellite, 'scope_id' => $house->id, 'is_active' => true,
        ]);

        // Different phones: a shared one would make the seed itself a "possible duplicate" no journey asked for.
        $this->family($ccf, $dxb, 'Maria', 'Dela Cruz', 'delacruz@nxtgen.test', '+971501000001', 'Miguel', 'Migs', GenderEnum::Boy, '2017-03-14');
        $this->family($ccf, $house, 'Grace', 'Santos', 'santos@nxtgen.test', '+971502000002', 'Bea', 'Bea', GenderEnum::Girl, '2018-07-02');

        $day = 0;
        foreach (self::DOOR_GUARDIANS as $project => $first) {
            $day++;
            $this->doorFamilies($ccf, $dxb, $house, $first, $day);
            $this->rollCallFamily($ccf, $dxb, $first, $day, self::ROLL_CALL_GIRLS[$project]);
        }
    }

    private function doorFamilies(Church $church, Satellite $dxb, Satellite $house, string $first, int $day): void
    {
        $lower = mb_strtolower($first);
        $kid = fn (string $name, GenderEnum $gender, string $dob, ?string $allergies = null): ChildDetailsData => new ChildDetailsData(
            firstName: $name, middleName: null, lastName: 'Cruz', nickname: $name, gender: $gender, dateOfBirth: $dob, allergies: $allergies,
        );
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Cruz', relationship: RelationshipEnum::Mother, phone: "+97150300000{$day}", email: "cruz-{$lower}@nxtgen.test"), [
            $kid('Amara', GenderEnum::Girl, "2019-03-0{$day}", 'Peanuts'),
            $kid('Ben', GenderEnum::Boy, "2021-06-0{$day}"),
            $kid('Noah', GenderEnum::Boy, "2017-05-0{$day}"),
            $kid('Lola', GenderEnum::Girl, "2022-06-0{$day}"),
            $kid('Gabe', GenderEnum::Boy, "2014-01-0{$day}"),
        ]);
        $this->register($church, $house, new GuardianDetailsData(firstName: $first, lastName: 'Villa', relationship: RelationshipEnum::Mother, phone: "+97150400000{$day}", email: "villa-{$lower}@nxtgen.test"), [
            new ChildDetailsData(firstName: 'Pia', middleName: null, lastName: 'Villa', nickname: 'Pia', gender: GenderEnum::Girl, dateOfBirth: "2019-09-0{$day}", allergies: null),
        ]);

        // As the door finds them: Noah inactive, Lola with no Level yet (her age suggests Level 1), Gabe Graduated.
        $cruz = fn (string $name) => Child::query()->where('church_id', $church->id)->where('last_name', 'Cruz')->where('first_name', $name)->where('date_of_birth', 'like', '%-0'.$day);
        $cruz('Noah')->update(['status' => ChildStatusEnum::Inactive]);
        $cruz('Lola')->update(['level' => null]);
        $cruz('Gabe')->update(['status' => ChildStatusEnum::Graduated, 'level' => 4]);
    }

    /** @param list<string> $girls */
    private function rollCallFamily(Church $church, Satellite $dxb, string $first, int $day, array $girls): void
    {
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Reyes', relationship: RelationshipEnum::Mother, phone: "+97150500000{$day}", email: 'reyes-'.mb_strtolower($first).'@nxtgen.test'), array_map(
            fn (string $name, int $month): ChildDetailsData => new ChildDetailsData(firstName: $name, middleName: null, lastName: 'Reyes', nickname: $name, gender: GenderEnum::Girl, dateOfBirth: "2019-0{$month}-0{$day}", allergies: null),
            $girls,
            [4, 8],
        ));
    }

    /** @param list<ChildDetailsData> $children */
    private function register(Church $church, Satellite $satellite, GuardianDetailsData $primary, array $children): void
    {
        (new RegisterFamilyOperation(new FamilyRegistrationData(
            church: $church,
            homeSatellite: $satellite,
            primary: $primary,
            children: $children,
            areaText: 'Al Barsha',
            privacyConsented: true,
            source: RegistrationSourceEnum::Import,
            notify: false,
        )))->handle();
    }

    private function family(Church $church, Satellite $satellite, string $guardianName, string $surname, string $email, string $phone, string $child, string $nickname, GenderEnum $gender, string $dob): void
    {
        (new RegisterFamilyOperation(new FamilyRegistrationData(
            church: $church,
            homeSatellite: $satellite,
            primary: new GuardianDetailsData(firstName: $guardianName, lastName: $surname, relationship: RelationshipEnum::Mother, phone: $phone, email: $email),
            children: [new ChildDetailsData(firstName: $child, middleName: null, lastName: $surname, nickname: $nickname, gender: $gender, dateOfBirth: $dob, allergies: null)],
            areaText: 'Al Barsha',
            privacyConsented: true,
            source: RegistrationSourceEnum::Import,
            notify: false,
        )))->handle();
    }
}
