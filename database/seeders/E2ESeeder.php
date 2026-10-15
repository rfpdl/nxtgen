<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
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
 */
final class E2ESeeder extends Seeder
{
    public const string HOUSE_ADMIN_EMAIL = 'house-admin@nxtgen.test';

    public function run(): void
    {
        $this->call(DemoChurchSeeder::class);

        $ccf = Organization::query()->where('slug', 'ccf')->firstOrFail();
        $dxb = Branch::query()->where('slug', 'ccf-dxb')->firstOrFail();
        $house = Branch::query()->where('slug', 'house-church')->firstOrFail();

        $houseAdmin = User::query()->create(['name' => 'House Admin', 'email' => self::HOUSE_ADMIN_EMAIL, 'email_verified_at' => now()]);
        RoleAssignment::query()->create([
            'organization_id' => $ccf->id, 'user_id' => $houseAdmin->id, 'role' => RoleEnum::Admin,
            'scope_type' => ScopeTypeEnum::Branch, 'scope_id' => $house->id, 'is_active' => true,
        ]);

        // Different phones: a shared one would make the seed itself a "possible duplicate" no journey asked for.
        $this->family($ccf, $dxb, 'Maria', 'Dela Cruz', 'delacruz@nxtgen.test', '+971501000001', 'Miguel', 'Migs', GenderEnum::Boy, '2017-03-14');
        $this->family($ccf, $house, 'Grace', 'Santos', 'santos@nxtgen.test', '+971502000002', 'Bea', 'Bea', GenderEnum::Girl, '2018-07-02');
    }

    private function family(Organization $org, Branch $branch, string $parent, string $surname, string $email, string $phone, string $child, string $nickname, GenderEnum $gender, string $dob): void
    {
        (new RegisterFamilyOperation(new FamilyRegistrationData(
            organization: $org,
            homeBranch: $branch,
            primary: new GuardianDetailsData(firstName: $parent, lastName: $surname, relationship: RelationshipEnum::Mother, phone: $phone, email: $email),
            children: [new ChildDetailsData(firstName: $child, middleName: null, lastName: $surname, nickname: $nickname, gender: $gender, dateOfBirth: $dob, allergies: null)],
            areaText: 'Al Barsha',
            privacyConsented: true,
            source: RegistrationSourceEnum::Import,
            notify: false,
        )))->handle();
    }
}
