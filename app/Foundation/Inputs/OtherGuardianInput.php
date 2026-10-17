<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\GuardianEditData;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One of the family's OTHER parents and guardians (a father, a grandparent …) on the registration forms and the
 * family edit form (DECISIONS 2026-10-17, after first look): GuardianDetailsInput's fields and rules — no email,
 * because only the primary guardian signs in. `id` names which saved guardian a row on the edit form is; a new row
 * has none, and registration ignores it.
 */
#[TypeScript]
final class OtherGuardianInput extends Data
{
    /** @param list<string> $ministries */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public RelationshipEnum $relationship,
        public string $phone,
        public array $ministries = [],
        public ?int $id = null,
    ) {}

    /**
     * GuardianDetailsInput's messages, said about someone else.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'Please enter their first name.',
            'last_name.required' => 'Please enter their last name.',
            'phone.required' => 'Please enter their mobile number.',
        ];
    }

    /**
     * GuardianDetailsInput's rules, plus the row's id.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            ...GuardianDetailsInput::rules(),
            'id' => ['nullable', 'integer'],
        ];
    }

    /** The guardian as the family's records keep it — names and phone trimmed (GuardianEditData::fromForm). */
    public function toEdit(): GuardianEditData
    {
        return GuardianEditData::fromForm($this->first_name, $this->last_name, $this->relationship, $this->phone, $this->ministries, $this->id);
    }
}
