<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\Models\Satellite;

/*
 * Valid edit-form payloads, shared by the parent (Family) and staff (Church) edit tests: the two edit the same
 * fields through the same inputs, so their tests send the same shape.
 */

/**
 * The family + parent edit form (FamilyDetailsInput).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function familyEdit(Satellite $satellite, array $overrides = []): array
{
    $guardian = ['first_name' => 'Janice', 'last_name' => 'Ramos-Lee', 'relationship' => 'mother', 'phone' => '0559998888', 'ministries' => ['Music']];
    if (is_array($overrides['guardian'] ?? null)) {
        $guardian = array_merge($guardian, $overrides['guardian']);
        unset($overrides['guardian']);
    }

    // Overrides replace top-level fields; a `guardian` override changes only the parent fields it names.
    return array_merge([
        'home_satellite_id' => $satellite->id,
        'area_text' => 'Deira',
        'fb_account' => 'janice.r',
        'wants_fb_group' => true,
        'in_dgroup' => true,
        'dgroup_area_text' => 'Barsha',
        'dgroup_leader_name' => 'Leah',
        'dgroup_leader_phone' => '0501231234',
        'wants_dgroup' => false,
        'lifestage' => 'Married with kids',
        'notes' => null,
        'guardian' => $guardian,
    ], $overrides);
}

/**
 * The registration form (RegisterFamilyInput) — parent self-registration and staff registering for a parent post the
 * same shape. One child; `guardians` (the family's other parents and guardians) only when an override gives them.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function familyRegistration(Satellite $satellite, string $email, array $overrides = []): array
{
    return array_merge([
        'home_satellite_id' => $satellite->id, 'area_text' => 'Deira', 'privacy_consented' => true,
        'guardian' => ['first_name' => 'Mira', 'last_name' => 'Bonza', 'relationship' => 'mother', 'phone' => '0523508443', 'email' => $email],
        'children' => [['first_name' => 'Clarion', 'middle_name' => null, 'last_name' => 'Bonza', 'nickname' => 'Clarion', 'gender' => 'boy', 'date_of_birth' => '2018-02-03', 'has_allergies' => false, 'allergies' => null]],
    ], $overrides);
}

/**
 * One child's edit form (ChildInput).
 *
 * @return array<string, mixed>
 */
function childEdit(): array
{
    return ['first_name' => 'Carlo', 'last_name' => 'Cruz', 'nickname' => 'Caloy', 'gender' => 'boy', 'date_of_birth' => '2016-06-06', 'has_allergies' => false];
}
