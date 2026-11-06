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
 * The journeys run on fifteen browser projects (by layout band and browser — Chrome, Safari, Firefox; see playwright.config.ts)
 * against one database, so each browser has its OWN families to check in
 * (DOOR_GUARDIANS): a Cruz family at CCF DXB — Amara (allergic), Ben, Noah (inactive), Lola (no Level), Gabe
 * (Graduated) — and a visiting Villa family at House Church (Pia). Birthdays differ per browser, so no two are
 * "possible duplicates". For Who's in (roll call), a Reyes family at CCF DXB with four Level 2 girls whose names are
 * the browser's own (ROLL_CALL_GIRLS): the browsers share one 1PM Session and its Squads, so each ticks its own. For
 * name cards, an Ocampo family of nine boys with no Card request (CARD_CHILDREN).
 */
final class E2ESeeder extends Seeder
{
    public const string HOUSE_ADMIN_EMAIL = 'house-admin@nxtgen.test';

    /** Each browser's own Lifeshaper at CCF DXB (`volunteer-desktop@…`): the password journey sets and uses its own password. */
    public const string VOLUNTEER_EMAIL = 'volunteer-%s@nxtgen.test';

    /** Browser project => the first name of its door families' parent. */
    public const array DOOR_GUARDIANS = [
        'phone-portrait' => 'Ana', 'phone-portrait-safari' => 'Bela', 'desktop' => 'Carla', 'phone-landscape' => 'Dina',
        'tablet-portrait' => 'Elsa', 'tablet-landscape' => 'Fara', 'phone-small' => 'Gina', 'tablet-edge' => 'Iva',
        'phone-landscape-safari' => 'Joan', 'desktop-safari' => 'Kara', 'phone-portrait-firefox' => 'Lina',
        'phone-landscape-firefox' => 'Meg', 'desktop-firefox' => 'Nora', 'tablet-portrait-safari' => 'Pat',
        'tablet-landscape-safari' => 'Vera',
    ];

    /**
     * Browser project => its Reyes family's Level 2 girls, named apart so no browser ticks another's: the first two for
     * the Who's in journey, the last two for the Attendance journey's roll call (one ticked, one not).
     */
    public const array ROLL_CALL_GIRLS = [
        'phone-portrait' => ['Sofia', 'Isabel', 'Lia', 'Gia'], 'phone-portrait-safari' => ['Mia', 'Elena', 'Nina', 'Ivy'],
        'desktop' => ['Ruth', 'Hope', 'Tala', 'Faith'], 'phone-landscape' => ['Jade', 'Kaye', 'Lea', 'Mae'],
        'tablet-portrait' => ['Nia', 'Opal', 'Pam', 'Rae'], 'tablet-landscape' => ['Sia', 'Tia', 'Una', 'Vea'],
        'phone-small' => ['Wyn', 'Xia', 'Yna', 'Zoe'], 'tablet-edge' => ['Ada', 'Cora', 'Dora', 'Eda'],
        'phone-landscape-safari' => ['Abi', 'Bia', 'Cai', 'Dia'], 'desktop-safari' => ['Eve', 'Kai', 'Hedy', 'Jem'],
        'phone-portrait-firefox' => ['Joni', 'Kia', 'Lua', 'Mona'], 'phone-landscape-firefox' => ['Pru', 'Oli', 'Pita', 'Qia'],
        'desktop-firefox' => ['Rue', 'Sue', 'Tess', 'Uma'], 'tablet-portrait-safari' => ['Bree', 'Cleo', 'Dot', 'Fern'],
        'tablet-landscape-safari' => ['Bo', 'Juno', 'Liv', 'Edie'],
    ];

    /**
     * Browser project => the middle name of its Reyes family's Amara, whose name card the scan journey types
     * (AMARAJOYREYES …): one key per browser, so each finds only its own child. She is never checked in.
     */
    public const array SCAN_MIDDLE_NAMES = [
        'phone-portrait' => 'Joy', 'phone-portrait-safari' => 'Kay', 'desktop' => 'Lee', 'phone-landscape' => 'Rose',
        'tablet-portrait' => 'Faye', 'tablet-landscape' => 'Gwen', 'phone-small' => 'Iris', 'tablet-edge' => 'June',
        'phone-landscape-safari' => 'Belle', 'desktop-safari' => 'Dawn', 'phone-portrait-firefox' => 'Hazel',
        'phone-landscape-firefox' => 'Pearl', 'desktop-firefox' => 'Wren', 'tablet-portrait-safari' => 'Skye',
        'tablet-landscape-safari' => 'Lark',
    ];

    /**
     * Each browser's Ocampo family at CCF DXB (name cards): imported, so no Card request — each card journey asks for a
     * new card for the first of them still without one. Nine, so the two card journeys (four children a run) and a
     * retry (CI retries once) find fresh children without a reseed, and the door's families stay untouched.
     */
    /**
     * Each browser's Torres family at CCF DXB: girls whose age suggests Level 3. The Level journey moves the first still at
     * Level 3 to Level 4 — three, so a retry finds another without a reseed.
     */
    public const array LEVEL_CHILDREN = ['Lexi', 'Lara', 'Luz'];

    public const array CARD_CHILDREN = ['Jon', 'Kit', 'Ned', 'Rex', 'Sam', 'Tom', 'Ugo', 'Vic', 'Wes'];

    /**
     * Each browser's Navarro family at CCF DXB (Moving-Up): Mika at Level 1 and Theo at Level 4, each with the browser's
     * own middle name (SCAN_MIDDLE_NAMES), so the journey ticks only its own two — "Mika Joy Navarro" — out of the whole
     * church, moves them up and puts them back.
     *
     * @var array<string, int>
     */
    public const array MOVING_UP_CHILDREN = ['Mika' => 1, 'Theo' => 4];

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

        $place = 0;
        foreach (self::DOOR_GUARDIANS as $project => $first) {
            // The browser's place, two digits ("01"…"13"): its birthdays' day of the month and its phones' last digits.
            $day = sprintf('%02d', ++$place);
            $this->doorFamilies($ccf, $dxb, $house, $first, $day);
            $this->rollCallFamily($ccf, $dxb, $first, $day, self::ROLL_CALL_GIRLS[$project], self::SCAN_MIDDLE_NAMES[$project]);
            $this->cardFamily($ccf, $dxb, $first, $day);
            $this->levelFamily($ccf, $dxb, $first, $day);
            $this->movingUpFamily($ccf, $dxb, $first, $day, self::SCAN_MIDDLE_NAMES[$project]);

            $volunteer = User::query()->create(['name' => 'Volunteer', 'email' => sprintf(self::VOLUNTEER_EMAIL, $project), 'email_verified_at' => now()]);
            ChurchSeeder::assign($ccf, $volunteer, RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite, $dxb->id);
        }
    }

    private function doorFamilies(Church $church, Satellite $dxb, Satellite $house, string $first, string $day): void
    {
        $lower = mb_strtolower($first);
        $kid = fn (string $name, GenderEnum $gender, string $dob, ?string $allergies = null): ChildDetailsData => new ChildDetailsData(
            firstName: $name, middleName: null, lastName: 'Cruz', nickname: $name, gender: $gender, dateOfBirth: $dob, allergies: $allergies,
        );
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Cruz', relationship: RelationshipEnum::Mother, phone: "+9715030000{$day}", email: "cruz-{$lower}@nxtgen.test"), [
            $kid('Amara', GenderEnum::Girl, "2019-03-{$day}", 'Peanuts'),
            $kid('Ben', GenderEnum::Boy, "2021-06-{$day}"),
            $kid('Noah', GenderEnum::Boy, "2017-05-{$day}"),
            $kid('Lola', GenderEnum::Girl, "2022-06-{$day}"),
            $kid('Gabe', GenderEnum::Boy, "2014-01-{$day}"),
        ]);
        $this->register($church, $house, new GuardianDetailsData(firstName: $first, lastName: 'Villa', relationship: RelationshipEnum::Mother, phone: "+9715040000{$day}", email: "villa-{$lower}@nxtgen.test"), [
            new ChildDetailsData(firstName: 'Pia', middleName: null, lastName: 'Villa', nickname: 'Pia', gender: GenderEnum::Girl, dateOfBirth: "2019-09-{$day}", allergies: null),
        ]);

        // As the door finds them: Noah inactive, Lola with no Level yet (her age suggests Level 1), Gabe Graduated.
        $cruz = fn (string $name) => Child::query()->where('church_id', $church->id)->where('last_name', 'Cruz')->where('first_name', $name)->where('date_of_birth', 'like', '%-'.$day);
        $cruz('Noah')->update(['status' => ChildStatusEnum::Inactive]);
        $cruz('Lola')->update(['level' => null]);
        $cruz('Gabe')->update(['status' => ChildStatusEnum::Graduated, 'level' => 4]);
    }

    /** @param list<string> $girls */
    private function rollCallFamily(Church $church, Satellite $dxb, string $first, string $day, array $girls, string $scanMiddle): void
    {
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Reyes', relationship: RelationshipEnum::Mother, phone: "+9715050000{$day}", email: 'reyes-'.mb_strtolower($first).'@nxtgen.test'), [
            ...array_map(
                fn (string $name, int $month): ChildDetailsData => new ChildDetailsData(firstName: $name, middleName: null, lastName: 'Reyes', nickname: $name, gender: GenderEnum::Girl, dateOfBirth: "2019-0{$month}-{$day}", allergies: null),
                $girls,
                [4, 8, 9, 7],
            ),
            new ChildDetailsData(firstName: 'Amara', middleName: $scanMiddle, lastName: 'Reyes', nickname: 'Amara', gender: GenderEnum::Girl, dateOfBirth: "2019-05-{$day}", allergies: null),
        ]);
    }

    private function levelFamily(Church $church, Satellite $dxb, string $first, string $day): void
    {
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Torres', relationship: RelationshipEnum::Mother, phone: "+9715070000{$day}", email: 'torres-'.mb_strtolower($first).'@nxtgen.test'), array_map(
            fn (string $name, int $month): ChildDetailsData => new ChildDetailsData(firstName: $name, middleName: null, lastName: 'Torres', nickname: $name, gender: GenderEnum::Girl, dateOfBirth: "2017-0{$month}-{$day}", allergies: null),
            self::LEVEL_CHILDREN,
            [3, 4, 5],
        ));
    }

    private function movingUpFamily(Church $church, Satellite $dxb, string $first, string $day, string $middle): void
    {
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Navarro', relationship: RelationshipEnum::Mother, phone: "+9715080000{$day}", email: 'navarro-'.mb_strtolower($first).'@nxtgen.test'), [
            new ChildDetailsData(firstName: 'Mika', middleName: $middle, lastName: 'Navarro', nickname: 'Mika', gender: GenderEnum::Girl, dateOfBirth: "2021-02-{$day}", allergies: null),
            new ChildDetailsData(firstName: 'Theo', middleName: $middle, lastName: 'Navarro', nickname: 'Theo', gender: GenderEnum::Boy, dateOfBirth: "2014-02-{$day}", allergies: null),
        ]);
        foreach (self::MOVING_UP_CHILDREN as $name => $level) {
            Child::query()->where('church_id', $church->id)->where('last_name', 'Navarro')->where('first_name', $name)->where('middle_name', $middle)->sole()
                ->forceFill(['level' => $level])->save();
        }
    }

    private function cardFamily(Church $church, Satellite $dxb, string $first, string $day): void
    {
        $this->register($church, $dxb, new GuardianDetailsData(firstName: $first, lastName: 'Ocampo', relationship: RelationshipEnum::Mother, phone: "+9715060000{$day}", email: 'ocampo-'.mb_strtolower($first).'@nxtgen.test'), array_map(
            fn (string $name, int $month): ChildDetailsData => new ChildDetailsData(firstName: $name, middleName: null, lastName: 'Ocampo', nickname: $name, gender: GenderEnum::Boy, dateOfBirth: "2020-0{$month}-{$day}", allergies: null),
            self::CARD_CHILDREN,
            [1, 2, 3, 4, 5, 6, 7, 8, 9],
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
