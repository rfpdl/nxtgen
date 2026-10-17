<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Enums\GenderEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One child on any form that creates or edits a child (parent registration, staff registration, add a child,
 * edit a child). The allergy question is an explicit yes/no (review B6): a blank box must never be read as
 * "no allergies", and a "no" keeps no details even if some were typed first.
 */
#[TypeScript]
final class ChildInput extends Data
{
    public function __construct(
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public string $nickname,
        public GenderEnum $gender,
        public string $date_of_birth,
        public bool $has_allergies,
        // An attribute, not rules(): only Spatie's field reference resolves `has_allergies` within THIS child.
        #[RequiredIf('has_allergies', 'true'), Max(500)]
        public ?string $allergies,
        public ?string $illnesses = null,
        public ?string $dislikes = null,
        public ?string $fears = null,
        public ?string $guardian_notes = null,
    ) {}

    /**
     * Plain words for parents.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'Please enter the first name.',
            'last_name.required' => 'Please enter the last name.',
            'nickname.required' => 'Please enter the name your child goes by.',
            'has_allergies.required' => 'Please answer: does your child have any allergies?',
            'has_allergies.boolean' => 'Please answer: does your child have any allergies?',
            'allergies.required_if' => 'Please tell us what your child is allergic to.',
            'gender.required' => 'Please choose boy or girl.',
            'gender.enum' => 'Please choose boy or girl.',
            'date_of_birth.required' => 'Please enter the birthday.',
            'date_of_birth.date' => 'Please enter the birthday.',
            'date_of_birth.before' => 'The birthday must be in the past.',
        ];
    }

    /**
     * The name Laravel's own messages use for a field (`The … field must not be greater than …`). The field was
     * `parent_notes` until refactor 06 renamed it to the glossary word; this keeps the words people read the same.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return ['guardian_notes' => 'parent notes'];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'nickname' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1990-01-01'],
            'has_allergies' => ['required', 'boolean'],
            'illnesses' => ['nullable', 'string', 'max:500'],
            'dislikes' => ['nullable', 'string', 'max:500'],
            'fears' => ['nullable', 'string', 'max:500'],
            'guardian_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDetails(): ChildDetailsData
    {
        return new ChildDetailsData(
            firstName: $this->first_name,
            middleName: $this->middle_name,
            lastName: $this->last_name,
            nickname: $this->nickname,
            gender: $this->gender,
            dateOfBirth: $this->date_of_birth,
            allergies: $this->has_allergies ? $this->allergies : null,
            illnesses: $this->illnesses,
            dislikes: $this->dislikes,
            fears: $this->fears,
            guardianNotes: $this->guardian_notes,
        );
    }
}
