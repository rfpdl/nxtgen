<?php

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Operations\Registration\RegisterFamilyOperation;

$ccf = Church::query()->where('slug', 'ccf')->firstOrFail();
$dxb = Satellite::query()->where('slug', 'ccf-dxb')->firstOrFail();
(new RegisterFamilyOperation(new FamilyRegistrationData(
    church: $ccf, homeSatellite: $dxb,
    primary: new GuardianDetailsData(firstName: 'Ana', lastName: 'Reyes', relationship: RelationshipEnum::Mother, phone: '+971505550001', email: 'reyes@brag.test'),
    children: [
        new ChildDetailsData(firstName: 'Amara', middleName: 'Joy', lastName: 'Reyes', nickname: 'Amara', gender: GenderEnum::Girl, dateOfBirth: '2019-05-03', allergies: 'Peanuts'),
        new ChildDetailsData(firstName: 'Ben', middleName: null, lastName: 'Reyes', nickname: 'Ben', gender: GenderEnum::Boy, dateOfBirth: '2021-06-04', allergies: null),
    ],
    areaText: 'Al Barsha', privacyConsented: true, source: RegistrationSourceEnum::Import, notify: false,
)))->handle();
echo "seeded\n";
